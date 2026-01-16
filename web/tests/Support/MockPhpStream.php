<?php

declare(strict_types=1);

final class MockPhpStream
{
    public static string $content = '';
    private int $index = 0;

    public function stream_open($path, $mode, $options, &$opened_path): bool
    {
        $this->index = 0;
        return true;
    }

    public function stream_read($count)
    {
        $ret = substr(static::$content, $this->index, $count);
        $this->index += strlen($ret);
        return $ret;
    }

    public function stream_eof(): bool
    {
        return $this->index >= strlen(static::$content);
    }

    public function stream_stat(): array
    {
        return [];
    }
}