<?php

namespace Tests\Unit;

use App\Services\NavigationTtsService;
use Illuminate\Process\Exceptions\ProcessTimedOutException;
use Illuminate\Process\PendingProcess;
use Illuminate\Process\ProcessResult;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Process;
use RuntimeException;
use Symfony\Component\Process\Exception\ProcessTimedOutException as SymfonyProcessTimedOutException;
use Symfony\Component\Process\Process as SymfonyProcess;
use Tests\TestCase;

class NavigationTtsServiceTest extends TestCase
{
    private string $testDirectory;

    protected function setUp(): void
    {
        parent::setUp();

        $this->testDirectory = storage_path('framework/testing/navigation-tts-'.bin2hex(random_bytes(4)));
        File::makeDirectory($this->testDirectory, 0755, true);
        $this->configureDependencies();
    }

    protected function tearDown(): void
    {
        File::deleteDirectory($this->testDirectory);

        parent::tearDown();
    }

    public function test_piper_success_returns_wav_and_preserves_vietnamese_utf8_input(): void
    {
        Process::fake(function (PendingProcess $process) {
            File::put($this->outputPath($process), 'RIFF'.str_repeat("\0", 300));

            return Process::result();
        });
        Process::preventStrayProcesses();

        $result = app(NavigationTtsService::class)->synthesize('Còn 50 mét, chuẩn bị rẽ trái.');

        $this->assertSame('audio/wav', $result['content_type']);
        $this->assertFalse($result['cached']);
        $this->assertStringStartsWith('RIFF', $result['audio']);
        Process::assertRan(function (PendingProcess $process) {
            return is_array($process->command)
                && $process->command[0] === config('services.navigation_tts.piper.binary')
                && $process->input === "Còn năm mươi mét, chuẩn bị rẽ trái.\n"
                && $process->timeout === 12;
        });
        $this->assertSame([], File::glob($this->testDirectory.'/cache/*.tmp.wav'));

        $cached = app(NavigationTtsService::class)->synthesize('Còn 50 mét, chuẩn bị rẽ trái.');

        $this->assertTrue($cached['cached']);
        Process::assertRanTimes(fn () => true, 1);
    }

    public function test_missing_binary_returns_a_controlled_unavailable_error(): void
    {
        File::delete(config('services.navigation_tts.piper.binary'));
        Process::fake();
        Process::preventStrayProcesses();

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Piper local chưa sẵn sàng');

        try {
            app(NavigationTtsService::class)->synthesize('Đi thẳng.');
        } finally {
            Process::assertNothingRan();
        }
    }

    public function test_missing_model_returns_a_controlled_unavailable_error(): void
    {
        File::delete(config('services.navigation_tts.piper.model'));
        Process::fake();
        Process::preventStrayProcesses();

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('model ONNX');

        try {
            app(NavigationTtsService::class)->synthesize('Đi thẳng.');
        } finally {
            Process::assertNothingRan();
        }
    }

    public function test_process_failure_is_controlled_and_removes_temporary_audio(): void
    {
        Process::fake(function (PendingProcess $process) {
            File::put($this->outputPath($process), 'partial');

            return Process::result(errorOutput: 'model load failed', exitCode: 1);
        });
        Process::preventStrayProcesses();

        try {
            app(NavigationTtsService::class)->synthesize('Rẽ phải.');
            $this->fail('The TTS process failure should throw an exception.');
        } catch (RuntimeException $exception) {
            $this->assertStringContainsString('Piper chưa tạo được audio', $exception->getMessage());
        }

        $this->assertSame([], File::glob($this->testDirectory.'/cache/*.tmp.wav'));
    }

    public function test_timeout_is_controlled_and_removes_temporary_audio(): void
    {
        Process::fake(fn () => $this->timeoutException());
        Process::preventStrayProcesses();

        try {
            app(NavigationTtsService::class)->synthesize('Tiếp tục đi thẳng.');
            $this->fail('The TTS timeout should throw an exception.');
        } catch (RuntimeException $exception) {
            $this->assertStringContainsString('timeout 12 giây', $exception->getMessage());
        }

        $this->assertSame([], File::glob($this->testDirectory.'/cache/*.tmp.wav'));
    }

    private function configureDependencies(): void
    {
        $binary = $this->testDirectory.'/piper';
        $model = $this->testDirectory.'/voice.onnx';
        $modelConfig = $this->testDirectory.'/voice.onnx.json';

        File::put($binary, 'fake binary');
        File::put($model, 'fake model');
        File::put($modelConfig, '{}');

        config()->set('services.navigation_tts.enabled', true);
        config()->set('services.navigation_tts.driver', 'piper');
        config()->set('services.navigation_tts.piper.binary', $binary);
        config()->set('services.navigation_tts.piper.model', $model);
        config()->set('services.navigation_tts.piper.config', $modelConfig);
        config()->set('services.navigation_tts.piper.cache', $this->testDirectory.'/cache');
        config()->set('services.navigation_tts.piper.voice', 'vi_VN-test');
        config()->set('services.navigation_tts.piper.timeout', 12);
    }

    private function outputPath(PendingProcess $process): string
    {
        $index = array_search('--output_file', $process->command, true);

        return $process->command[$index + 1];
    }

    private function timeoutException(): ProcessTimedOutException
    {
        $process = new SymfonyProcess(['piper']);
        $process->setTimeout(12);

        return new ProcessTimedOutException(
            new SymfonyProcessTimedOutException($process, SymfonyProcessTimedOutException::TYPE_GENERAL),
            new ProcessResult($process)
        );
    }
}
