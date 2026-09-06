<?php

namespace App\Services;

use Illuminate\Process\Exceptions\ProcessTimedOutException;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Process;
use RuntimeException;
use Throwable;

class NavigationTtsService
{
    public function synthesize(string $text): array
    {
        if (! (bool) config('services.navigation_tts.enabled', true)) {
            throw new RuntimeException('Navigation TTS đang bị tắt trong cấu hình môi trường.');
        }

        $text = trim(preg_replace('/\s+/u', ' ', $text) ?? $text);
        $text = $this->normalizeForVietnameseSpeech($text);
        if ($text === '' || mb_strlen($text) > 320) {
            throw new RuntimeException('Nội dung hướng dẫn giọng nói không hợp lệ.');
        }

        $driver = strtolower(trim((string) config('services.navigation_tts.driver', 'piper')));
        if ($driver !== 'piper') {
            throw new RuntimeException('NAV_TTS_DRIVER phải là piper. Bản này không còn cần Microsoft/Azure.');
        }

        return $this->synthesizeWithPiper($text);
    }

    private function synthesizeWithPiper(string $text): array
    {
        if (! function_exists('proc_open')) {
            throw new RuntimeException('PHP đang tắt proc_open. Hãy bỏ proc_open khỏi disable_functions trong php.ini rồi khởi động lại Apache.');
        }

        $binary = $this->resolvePath((string) config('services.navigation_tts.piper.binary', 'tools/piper/piper.exe'));
        $model = $this->resolvePath((string) config('services.navigation_tts.piper.model', 'storage/app/navigation_tts/voices/vi_VN-vais1000-medium.onnx'));
        $modelConfig = $this->resolvePath((string) config('services.navigation_tts.piper.config', 'storage/app/navigation_tts/voices/vi_VN-vais1000-medium.onnx.json'));
        $voice = (string) config('services.navigation_tts.piper.voice', 'vi_VN-vais1000-medium');
        $timeout = max(3, min(30, (int) config('services.navigation_tts.piper.timeout', 12)));

        $missing = [];
        foreach (['Piper' => $binary, 'model ONNX' => $model, 'model config' => $modelConfig] as $label => $path) {
            if (! is_file($path)) {
                $missing[] = $label.': '.$path;
            }
        }
        if ($missing) {
            throw new RuntimeException('Piper local chưa sẵn sàng. Chạy scripts/install_piper_tts.ps1 từ thư mục project. Thiếu: '.implode(' | ', $missing));
        }

        $cacheDir = $this->resolvePath((string) config('services.navigation_tts.piper.cache', 'storage/app/navigation_tts/cache'));
        if (! File::isDirectory($cacheDir)) {
            File::makeDirectory($cacheDir, 0755, true);
        }

        // Cache có version để thay đổi tốc độ/độ rõ sẽ tạo audio mới, không dùng WAV cũ.
        $modelStamp = @filemtime($model) ?: 0;
        $voiceProfile = 'nav-clear-v2|length=1.12|noise=0.55|noise_w=0.65';
        $cacheKey = sha1($voice.'|'.$modelStamp.'|'.$voiceProfile.'|'.$text);
        $path = $cacheDir.DIRECTORY_SEPARATOR.$cacheKey.'.wav';

        if (File::exists($path) && File::size($path) > 256) {
            return [
                'audio' => File::get($path),
                'content_type' => 'audio/wav',
                'cached' => true,
                'voice' => $voice,
            ];
        }

        $tempPath = $cacheDir.DIRECTORY_SEPARATOR.$cacheKey.'.'.bin2hex(random_bytes(4)).'.tmp.wav';
        $command = [
            $binary,
            '--model', $model,
            '--config', $modelConfig,
            // Cho Piper tự đọc chậm hơn ở nguồn để giữ độ rõ của âm thanh.
            // Không kéo chậm WAV bằng playbackRate của trình duyệt nữa.
            '--length_scale', '1.12',
            '--noise_scale', '0.55',
            '--noise_w', '0.65',
            '--sentence_silence', '0.24',
            '--output_file', $tempPath,
        ];

        try {
            try {
                // Command dạng mảng không đi qua shell; nội dung UTF-8 chỉ được truyền qua stdin.
                $result = Process::path(dirname($binary))
                    ->timeout($timeout)
                    ->input($text."\n")
                    ->run($command);
            } catch (ProcessTimedOutException $exception) {
                throw new RuntimeException('Piper tạo giọng quá lâu (timeout '.$timeout.' giây).', previous: $exception);
            } catch (Throwable $exception) {
                throw new RuntimeException('Không khởi chạy được Piper local. Kiểm tra binary và quyền chạy file.', previous: $exception);
            }

            if ($result->failed() || ! is_file($tempPath) || filesize($tempPath) <= 256) {
                $detail = trim($result->errorOutput() ?: $result->output());
                if (mb_strlen($detail) > 280) {
                    $detail = mb_substr($detail, 0, 280).'…';
                }
                throw new RuntimeException('Piper chưa tạo được audio'.($detail !== '' ? ': '.$detail : ' (exit '.$result->exitCode().').'));
            }

            // Rename gần-atomic: hai request cùng câu cũng không làm hỏng cache.
            if (! @rename($tempPath, $path) && ! File::exists($path)) {
                File::copy($tempPath, $path);
            }
        } finally {
            if (is_file($tempPath)) {
                @unlink($tempPath);
            }
        }

        if (! File::exists($path) || File::size($path) <= 256) {
            throw new RuntimeException('Piper chưa tạo được file audio hợp lệ.');
        }

        return [
            'audio' => File::get($path),
            'content_type' => 'audio/wav',
            'cached' => false,
            'voice' => $voice,
        ];
    }


    /**
     * Chuẩn hoá câu dẫn đường cho model tiếng Việt dễ phát âm hơn.
     * Ví dụ: "Còn 120 mét" -> "Còn một trăm hai mươi mét".
     */
    private function normalizeForVietnameseSpeech(string $text): string
    {
        $text = preg_replace('/\bQL\.?\s*(\d+)/iu', 'quốc lộ $1', $text) ?? $text;
        $text = preg_replace('/\bĐT\.?\s*(\d+)/iu', 'tỉnh lộ $1', $text) ?? $text;
        $text = preg_replace('/\bkm\b/iu', 'ki lô mét', $text) ?? $text;

        // Số thập phân thường gặp khi đọc khoảng cách theo km.
        $text = preg_replace_callback(
            '/(?<![\pL\d])(\d{1,4})[\.,](\d{1,2})(?![\pL\d])/u',
            function (array $match): string {
                $fraction = implode(' ', array_map(
                    fn (string $digit): string => $this->digitWord((int) $digit),
                    str_split($match[2])
                ));

                return $this->integerToVietnameseWords((int) $match[1]).' phẩy '.$fraction;
            },
            $text
        ) ?? $text;

        // Chỉ đổi số đứng độc lập; không phá tên kiểu A1, QL47, mã đơn hàng...
        $text = preg_replace_callback(
            '/(?<![\pL\d])(\d{1,6})(?![\pL\d])/u',
            fn (array $match): string => $this->integerToVietnameseWords((int) $match[1]),
            $text
        ) ?? $text;

        // Thêm nhịp tự nhiên cho câu điều hướng dài.
        $text = preg_replace('/\s*,\s*/u', ', ', $text) ?? $text;
        $text = preg_replace('/\s+([\.?!])/u', '$1', $text) ?? $text;

        return trim($text);
    }

    private function integerToVietnameseWords(int $number): string
    {
        if ($number === 0) {
            return 'không';
        }

        if ($number < 0) {
            return 'âm '.$this->integerToVietnameseWords(abs($number));
        }

        if ($number >= 1000000) {
            return (string) $number;
        }

        $parts = [];
        $thousands = intdiv($number, 1000);
        $rest = $number % 1000;

        if ($thousands > 0) {
            $parts[] = $this->readVietnameseBlock($thousands, false);
            $parts[] = 'nghìn';
        }

        if ($rest > 0) {
            $parts[] = $this->readVietnameseBlock($rest, $thousands > 0 && $rest < 100);
        }

        return trim(implode(' ', array_filter($parts)));
    }

    private function readVietnameseBlock(int $number, bool $forceHundreds): string
    {
        $hundreds = intdiv($number, 100);
        $rest = $number % 100;
        $words = [];

        if ($hundreds > 0 || $forceHundreds) {
            $words[] = $this->digitWord($hundreds);
            $words[] = 'trăm';
        }

        if ($rest === 0) {
            return implode(' ', $words);
        }

        $tens = intdiv($rest, 10);
        $ones = $rest % 10;

        if ($tens === 0) {
            if ($hundreds > 0 || $forceHundreds) {
                $words[] = 'lẻ';
            }
            $words[] = $this->digitWord($ones);
            return implode(' ', $words);
        }

        if ($tens === 1) {
            $words[] = 'mười';
        } else {
            $words[] = $this->digitWord($tens);
            $words[] = 'mươi';
        }

        if ($ones > 0) {
            if ($ones === 1 && $tens > 1) {
                $words[] = 'mốt';
            } elseif ($ones === 5 && $tens >= 1) {
                $words[] = 'lăm';
            } else {
                $words[] = $this->digitWord($ones);
            }
        }

        return implode(' ', $words);
    }

    private function digitWord(int $digit): string
    {
        return match ($digit) {
            0 => 'không',
            1 => 'một',
            2 => 'hai',
            3 => 'ba',
            4 => 'bốn',
            5 => 'năm',
            6 => 'sáu',
            7 => 'bảy',
            8 => 'tám',
            9 => 'chín',
            default => (string) $digit,
        };
    }

    private function resolvePath(string $path): string
    {
        $path = trim($path);
        if ($path === '') {
            return '';
        }

        // Windows absolute path: C:\\... hoặc UNC \\\\server\\...
        if (preg_match('/^[A-Za-z]:[\\\\\/]/', $path) || str_starts_with($path, '\\\\')) {
            return $path;
        }

        // Unix absolute path.
        if (str_starts_with($path, '/')) {
            return $path;
        }

        return base_path(str_replace(['/', '\\'], DIRECTORY_SEPARATOR, $path));
    }
}
