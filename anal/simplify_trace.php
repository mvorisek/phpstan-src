<?php

class File
{
    private $handle;
    private $handleOut;
    private $buffer = '';

    public function __construct(string $filepath, string $outFilepath) {
        $this->handle = fopen($filepath, 'r');
        if (stream_set_read_buffer($this->handle, 256 * 1024) !== 0) {
            $this->assertNotReached();
        }

        unlink($outFilepath);
        $this->handleOut = fopen($outFilepath, 'w');
//        if (stream_set_write_buffer($this->handleOut, 256 * 1024) !== 0) {
//            $this->assertNotReached();
//        }
    }

    public function getNextLine(): ?string
    {
        while (($p = strpos($this->buffer, "\n")) === false) {
            if ($this->handle === null) {
                $res = $this->buffer;
                $this->buffer = null;
                return $res;
            }

            $c = 16 * 1024;
            $d = fread($this->handle, $c);
            if (strlen($d) < $c) {
                fclose($this->handle);
                $this->handle = null;
            }

            $this->buffer .= $d;
        }

        $l = substr($this->buffer, 0, $p);
        $this->buffer = substr($this->buffer, $p + 1);
        return $l;
    }

    public function assertNotReached(): ?string
    {
        throw new \Exception('Code should be never reached');
    }

    public function getFixedLine(): ?string
    {
        $l = $this->getNextLine();
        if (substr($l, 0, 1) !== ' ') {
            if (preg_match('~^TRACE START ~', $l)) {
                return $this->getFixedLine(); // skip
            }

            $this->assertNotReached();
        }

        $origLLen = strlen($l);
        $l = preg_replace('~^.{22}(?= *(?:->|>=>))~', '', $l);
        if (strlen($l) === $origLLen) {
           if (preg_match('~^TRACE END ~', $this->getNextLine())) { // verify end
                if ($this->getNextLine() !== '') { // skip
                    $this->assertNotReached();
                }
                if ($this->getNextLine() !== '') { // skip
                    $this->assertNotReached();
                }
                if ($this->getNextLine() === null) {
                    return null;
                }
            }

            $this->assertNotReached();
        }
        $l = preg_replace('~C:\\\\Users\\\\mvorisek\\\\Desktop\\\\forks\\\\phpstan-src(?=\\\\)~', 'C:\\\\p-src', $l);
        $l = preg_replace('~(?<=resource\()\d{1,20}(?=\))~', 'x', $l);

        return $l;
    }

    public function writeLine(string $line): void
    {
        fwrite($this->handleOut, $line . "\n");
    }

    public function convertFile(): void
    {
        $statLines = 0;
        $statSize = 0; // based on fixed line size
        $statStartTs = microtime(true);
        while (true) {
            $l = $this->getFixedLine();
            if ($l === null) {
                fclose($this->handleOut);

                break;
            }
            $this->writeLine($l);

            $statLines++;
            $statSize += strlen($l);
            if (($statLines % (1000 * 1000)) === 0) {
                echo 'processed ' . ($statLines / (1000 * 1000)) . 'M lines (' . round($statSize / (1024 * 1024) / (microtime(true) - $statStartTs), 2) . ' MB / s)' . "\n";
            }
        }
    }
}

foreach (array_diff(scandir(__DIR__ . '/in'), ['.', '..']) as $n) {

    if (!preg_match('~c1~', $n)) {
        continue;
    }

    echo '-- starting: ' . $n . "\n";
    $fNoOpcache = new File(__DIR__ . '/in/' . $n, __DIR__ . '/out/' . $n);
    $fNoOpcache->convertFile();
    echo '-- finished: ' . $n . "\n\n";
}
