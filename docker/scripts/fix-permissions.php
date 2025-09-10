<?php

/**
 * Este script resolve o problema de permissões no Docker
 * garantindo que os arquivos criados pelo PHP tenham as permissões corretas.
 *
 * Nota: Ele só deve ser aplicado aos comandos específicos, não globalmente
 */

// Em vez de registrar o wrapper automaticamente, vamos apenas definir a classe
// para ser usada explicitamente quando necessário

// Classe wrapper que sobrescreve o comportamento padrão de criação de arquivos
class FilePermissionWrapper {
    private $context;
    private $handle;
    private $mode;
    private $path;

    public function stream_open($path, $mode, $options, &$opened_path) {
        $this->path = $path;
        $this->mode = $mode;

        $opts = stream_context_get_options($this->context);

        if (strpos($mode, 'w') !== false || strpos($mode, 'a') !== false || strpos($mode, 'x') !== false || strpos($mode, 'c') !== false) {
            // Está criando ou modificando um arquivo
            $this->handle = fopen($path, $mode, $options, $opened_path);

            if ($this->handle) {
                // Ajusta as permissões imediatamente
                chmod(str_replace('file://', '', $path), 0666);
            }

            return (bool) $this->handle;
        } else {
            // Apenas leitura, comportamento normal
            $this->handle = fopen($path, $mode, $options, $opened_path);
            return (bool) $this->handle;
        }
    }

    public function stream_close() {
        return fclose($this->handle);
    }

    public function stream_read($count) {
        return fread($this->handle, $count);
    }

    public function stream_write($data) {
        return fwrite($this->handle, $data);
    }

    public function stream_tell() {
        return ftell($this->handle);
    }

    public function stream_eof() {
        return feof($this->handle);
    }

    public function stream_seek($offset, $whence) {
        return fseek($this->handle, $offset, $whence) === 0;
    }

    public function stream_stat() {
        return fstat($this->handle);
    }

    public function stream_flush() {
        return fflush($this->handle);
    }

    public function stream_set_option($option, $arg1, $arg2) {
        return true;
    }

    public function url_stat($path, $flags) {
        return stat($path);
    }

    public function dir_opendir($path, $options) {
        $this->handle = opendir($path);
        return (bool) $this->handle;
    }

    public function dir_readdir() {
        return readdir($this->handle);
    }

    public function dir_rewinddir() {
        return rewinddir($this->handle);
    }

    public function dir_closedir() {
        return closedir($this->handle);
    }

    public function mkdir($path, $mode, $options) {
        if (mkdir($path, $mode, $options)) {
            chmod($path, 0777);
            return true;
        }
        return false;
    }

    public function rmdir($path, $options) {
        return rmdir($path);
    }

    public function unlink($path) {
        return unlink($path);
    }

    public function rename($path_from, $path_to) {
        if (rename($path_from, $path_to)) {
            chmod($path_to, 0666);
            return true;
        }
        return false;
    }

    public function stream_cast($cast_as) {
        return $this->handle;
    }

    public function stream_metadata($path, $option, $value) {
        if ($option == STREAM_META_TOUCH) {
            $result = touch($path);
            chmod($path, 0666);
            return $result;
        }
        return false;
    }
}
