<?php

declare(strict_types=1);

/**
 * Chill Drink - Shipper Home Branch Reassign Fix V26
 *
 * Mục tiêu:
 * - Super Admin được đổi Home Branch của shipper ngay, không bắt shipper phải về chi nhánh cũ trước.
 * - Không sửa/xóa COD lịch sử hoặc đơn test cũ.
 * - Không ghi đè nguyên controller/view đang chạy; chỉ gỡ đúng guard chặn điều chuyển và mở select Home Branch.
 */

$root = realpath(__DIR__ . '/..');
if ($root === false || !is_file($root . DIRECTORY_SEPARATOR . 'artisan')) {
    fwrite(STDERR, "[LOI] Hay dat ZIP/thu muc patch vao ROOT Laravel (noi co file artisan) roi chay lai.\n");
    exit(1);
}

$needles = [
    'Shipper đang trên đường quay về chi nhánh. Hãy chờ tới nơi trước khi điều chuyển.',
    'Hãy chờ tới nơi trước khi điều chuyển',
    'đang trên đường quay về chi nhánh',
];

$backupRoot = $root . DIRECTORY_SEPARATOR . 'storage' . DIRECTORY_SEPARATOR . 'app' . DIRECTORY_SEPARATOR . 'patch_backups'
    . DIRECTORY_SEPARATOR . 'SHIPPER_HOME_BRANCH_V26_' . date('Ymd_His');

@mkdir($backupRoot, 0777, true);

function allPhpFiles(string $base): array
{
    if (!is_dir($base)) {
        return [];
    }

    $files = [];
    $it = new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator($base, FilesystemIterator::SKIP_DOTS)
    );

    foreach ($it as $file) {
        if (!$file->isFile()) {
            continue;
        }
        $path = $file->getPathname();
        if (str_ends_with($path, '.php') || str_ends_with($path, '.blade.php')) {
            $files[] = $path;
        }
    }

    return $files;
}

function backupFile(string $root, string $backupRoot, string $path): void
{
    $relative = ltrim(str_replace('\\', '/', substr($path, strlen($root))), '/');
    $dest = $backupRoot . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $relative);
    @mkdir(dirname($dest), 0777, true);
    if (!copy($path, $dest)) {
        throw new RuntimeException("Khong the backup file: {$relative}");
    }
}

/**
 * Tìm block if nhỏ nhất bao quanh chuỗi lỗi và xóa block đó.
 * Dùng token_get_all để không bị nhầm dấu ngoặc trong string/comment.
 */
function removeGuardIfContainingNeedle(string $content, string $needle): array
{
    $needlePos = strpos($content, $needle);
    if ($needlePos === false) {
        return [$content, false, null];
    }

    $tokens = token_get_all($content);
    $rows = [];
    $offset = 0;
    foreach ($tokens as $idx => $token) {
        $text = is_array($token) ? $token[1] : $token;
        $id = is_array($token) ? $token[0] : null;
        $rows[] = [
            'idx' => $idx,
            'id' => $id,
            'text' => $text,
            'start' => $offset,
            'end' => $offset + strlen($text),
        ];
        $offset += strlen($text);
    }

    $candidates = [];
    $n = count($rows);
    for ($i = 0; $i < $n; $i++) {
        if ($rows[$i]['id'] !== T_IF) {
            continue;
        }

        $open = null;
        $parenDepth = 0;
        $seenParen = false;
        for ($j = $i + 1; $j < $n; $j++) {
            $t = $rows[$j]['text'];
            if ($t === '(') {
                $parenDepth++;
                $seenParen = true;
            } elseif ($t === ')') {
                $parenDepth--;
            } elseif ($t === '{' && $seenParen && $parenDepth === 0) {
                $open = $j;
                break;
            } elseif ($t === ';' && $seenParen && $parenDepth === 0) {
                // if một dòng không có ngoặc nhọn: không tự sửa để tránh phá code.
                break;
            }
        }
        if ($open === null) {
            continue;
        }

        $depth = 0;
        $close = null;
        for ($j = $open; $j < $n; $j++) {
            $t = $rows[$j]['text'];
            if ($t === '{') {
                $depth++;
            } elseif ($t === '}') {
                $depth--;
                if ($depth === 0) {
                    $close = $j;
                    break;
                }
            }
        }
        if ($close === null) {
            continue;
        }

        $start = $rows[$i]['start'];
        $end = $rows[$close]['end'];
        if ($needlePos >= $start && $needlePos < $end) {
            $candidates[] = [$start, $end, $i, $close];
        }
    }

    if (!$candidates) {
        return [$content, false, 'Tim thay thong bao nhung khong xac dinh duoc block if bao quanh.'];
    }

    usort($candidates, fn ($a, $b) => (($a[1] - $a[0]) <=> ($b[1] - $b[0])));
    [$start, $end, $ifTokenIdx, $closeTokenIdx] = $candidates[0];

    // Nếu block có else/elseif ngay sau thì không tự xóa để tránh biến đổi nghiệp vụ ngoài ý muốn.
    $nextIdx = $closeTokenIdx + 1;
    while ($nextIdx < $n && in_array($rows[$nextIdx]['id'], [T_WHITESPACE, T_COMMENT, T_DOC_COMMENT], true)) {
        $nextIdx++;
    }
    if ($nextIdx < $n && in_array($rows[$nextIdx]['id'], [T_ELSE, T_ELSEIF], true)) {
        return [$content, false, 'Guard co ELSE/ELSEIF; patch dung lai de tranh xoa nham nghiep vu.'];
    }

    // Ăn luôn indentation/trailing newline của guard nếu có.
    $lineStart = strrpos(substr($content, 0, $start), "\n");
    if ($lineStart !== false) {
        $candidatePrefix = substr($content, $lineStart + 1, $start - ($lineStart + 1));
        if (trim($candidatePrefix) === '') {
            $start = $lineStart + 1;
        }
    }
    if (substr($content, $end, 2) === "\r\n") {
        $end += 2;
    } elseif (substr($content, $end, 1) === "\n") {
        $end += 1;
    }

    $replacement = "        // [V26] Cho phep Super Admin doi Home Branch ngay; khong bat shipper ve chi nhanh cu truoc.\n";
    $new = substr($content, 0, $start) . $replacement . substr($content, $end);

    return [$new, true, null];
}

function ensureSuperAdminBranchSelectUnlocked(string $content): array
{
    $marker = 'SHIPPER_HOME_BRANCH_V26_UI';
    if (str_contains($content, $marker)) {
        return [$content, false];
    }

    if (!str_contains($content, 'name="branch_id"') && !str_contains($content, "name='branch_id'")) {
        return [$content, false];
    }

    $snippet = <<<'BLADE'

{{-- SHIPPER_HOME_BRANCH_V26_UI: Super Admin có thể đổi Home Branch ngay, kể cả shipper đang trên đường về chi nhánh cũ. --}}
@if(auth()->user()?->isSuperAdmin())
<script>
document.addEventListener('DOMContentLoaded', function () {
    document.querySelectorAll('select[name="branch_id"]').forEach(function (select) {
        if (!select.closest('.modal')) return;
        select.disabled = false;
        select.removeAttribute('disabled');
        select.removeAttribute('readonly');
    });

    document.querySelectorAll('.text-danger, .invalid-feedback, .form-text').forEach(function (node) {
        const text = (node.textContent || '').toLowerCase();
        if (text.includes('quay về chi nhánh') && text.includes('điều chuyển')) {
            node.remove();
        }
    });
});
</script>
@endif
BLADE;

    $pos = strrpos($content, '@endsection');
    if ($pos !== false) {
        $content = substr($content, 0, $pos) . $snippet . "\n" . substr($content, $pos);
    } else {
        $content .= $snippet . "\n";
    }

    return [$content, true];
}

$phpCandidates = array_merge(
    allPhpFiles($root . DIRECTORY_SEPARATOR . 'app'),
    allPhpFiles($root . DIRECTORY_SEPARATOR . 'routes')
);

$modified = [];
$guardRemoved = 0;
$errors = [];

foreach ($phpCandidates as $path) {
    $content = @file_get_contents($path);
    if ($content === false) {
        continue;
    }

    $matchedNeedle = null;
    foreach ($needles as $needle) {
        if (str_contains($content, $needle)) {
            $matchedNeedle = $needle;
            break;
        }
    }
    if ($matchedNeedle === null) {
        continue;
    }

    $original = $content;
    $changedAny = false;

    // Có thể có nhiều guard trong cùng file.
    while (true) {
        $found = null;
        foreach ($needles as $needle) {
            if (str_contains($content, $needle)) {
                $found = $needle;
                break;
            }
        }
        if ($found === null) {
            break;
        }

        [$patched, $changed, $error] = removeGuardIfContainingNeedle($content, $found);
        if (!$changed) {
            $errors[] = str_replace($root . DIRECTORY_SEPARATOR, '', $path) . ': ' . ($error ?? 'Khong the go guard.');
            break;
        }
        $content = $patched;
        $changedAny = true;
        $guardRemoved++;
    }

    if ($changedAny) {
        backupFile($root, $backupRoot, $path);
        file_put_contents($path, $content);
        $modified[] = $path;
    }
}

// Mở khóa select Home Branch ở đúng trang staff cho Super Admin; không thay quyền Admin thường.
$staffViews = [
    $root . DIRECTORY_SEPARATOR . 'resources' . DIRECTORY_SEPARATOR . 'views' . DIRECTORY_SEPARATOR . 'admin' . DIRECTORY_SEPARATOR . 'staff' . DIRECTORY_SEPARATOR . 'index.blade.php',
    $root . DIRECTORY_SEPARATOR . 'resources' . DIRECTORY_SEPARATOR . 'views' . DIRECTORY_SEPARATOR . 'admin' . DIRECTORY_SEPARATOR . 'super-admin' . DIRECTORY_SEPARATOR . 'staff.blade.php',
    $root . DIRECTORY_SEPARATOR . 'resources' . DIRECTORY_SEPARATOR . 'views' . DIRECTORY_SEPARATOR . 'admin' . DIRECTORY_SEPARATOR . 'super-admin' . DIRECTORY_SEPARATOR . 'staff' . DIRECTORY_SEPARATOR . 'index.blade.php',
];

foreach ($staffViews as $path) {
    if (!is_file($path)) {
        continue;
    }
    $content = file_get_contents($path);
    [$patched, $changed] = ensureSuperAdminBranchSelectUnlocked($content);
    if ($changed) {
        backupFile($root, $backupRoot, $path);
        file_put_contents($path, $patched);
        $modified[] = $path;
    }
}

$modified = array_values(array_unique($modified));

if ($guardRemoved === 0) {
    echo "[CANH BAO] Khong tim thay guard dung chuoi loi da biet trong app/routes.\n";
    echo "Patch UI van duoc ap dung neu tim thay trang staff.\n";
    echo "Khong ghi de controller cu vi source dang chay co ve moi hon source GitHub/ZIP da gui.\n";
}

// Syntax check các file PHP thuần đã sửa.
$lintFailed = false;
foreach ($modified as $path) {
    if (str_ends_with($path, '.blade.php')) {
        continue;
    }
    $cmd = 'php -l ' . escapeshellarg($path) . ' 2>&1';
    exec($cmd, $out, $code);
    if ($code !== 0) {
        $lintFailed = true;
        echo "[LOI PHP] " . str_replace($root . DIRECTORY_SEPARATOR, '', $path) . "\n";
        echo implode("\n", $out) . "\n";
    }
}

if ($lintFailed) {
    echo "\n[THAT BAI] Co file PHP loi cu phap. Ban backup nam tai:\n{$backupRoot}\n";
    exit(2);
}

if (is_file($root . DIRECTORY_SEPARATOR . 'artisan')) {
    $cwd = getcwd();
    chdir($root);
    @passthru('php artisan optimize:clear');
    chdir($cwd ?: $root);
}

echo "\n================ KET QUA V26 ================\n";
echo "Guard chan dieu chuyen da go: {$guardRemoved}\n";
echo "File da sua: " . count($modified) . "\n";
foreach ($modified as $path) {
    echo ' - ' . str_replace($root . DIRECTORY_SEPARATOR, '', $path) . "\n";
}
if ($errors) {
    echo "\nCanh bao:\n";
    foreach ($errors as $error) {
        echo " - {$error}\n";
    }
}
echo "\nBackup: {$backupRoot}\n";
echo "Nghiep vu sau patch: Super Admin doi Home Branch truc tiep; khong cho doi cho Admin thuong; khong sua COD/don lich su.\n";
echo "=============================================\n";
