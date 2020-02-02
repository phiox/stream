<?php

namespace Phiox\Stream\Wrapper;

use Phiox\Stream\WrapperInterface;

abstract class AbstractWrapper implements WrapperInterface
{
    /**
     * @var resource Context resource-handle
     */
    public $context;

    /**
     * @var resource Stream resource-handle
     */
    protected $resource;

    /**
     * @var string Wrapper protocol
     */
    protected $protocol = 'phiox-stream';

    /**
     * @var bool Flag for isWritable()
     */
    protected $writable = true;

    /**
     * @var bool Flag for isReadable()
     */
    protected $readable = true;

    /**
     * @var bool Flag for isSeekable()
     */
    protected $seekable = true;

    /**
     * Register stream wrapper
     *
     * @param  string $protocol
     * @return void
     */
    public function register($protocol = null)
    {
        $this->protocol = (empty($protocol) ? $this->protocol : $protocol);

        if (!in_array($this->protocol, stream_get_wrappers())) {
            stream_wrapper_register($this->protocol, get_called_class());
        }
    }

    /**
     * Unregister stream wrapper
     *
     * @return void
     */
    public function unregister()
    {
        if (in_array($this->protocol, stream_get_wrappers())) {
            stream_wrapper_unregister($this->protocol);
            stream_wrapper_restore($this->protocol);
        }
    }

    /**
     * Call method on the wrapped stream resource.
     *
     * @param  string $function
     * @param  mixed  $arguments
     * @return mixed
     */
    protected function callInner($function, $arguments)
    {
        try {
            $this->unregister();

            $silent = (empty($function) ? false : $function[0] === '@');
            $function = ltrim($function, '@');

            if ($silent) {
                $result = @call_user_func_array($function, $arguments);
            } else {
                $result = call_user_func_array($function, $arguments);
            }
        } finally {
            $this->register();

            var_dump($function, $result);

            return $result;
        }
    }

    /**
     * WrapperInterface method
     *
     * @param  string      $path
     * @param  string      $mode
     * @param  int         $options
     * @param  string|null $opened_path
     * @return bool
     */
    public function stream_open($path, $mode, $options, &$opened_path = null)
    {
        $opt = stream_context_get_options($this->context);
        $arguments = [$path, $mode, (bool)($options & STREAM_USE_PATH)];

        if (!($options & static::STREAM_OPEN_FOR_INCLUDE)) {
            $arguments[] = $this->context;
        }

        if (is_array($opt[static::class]) && !isset($opt[static::class][$this->protocol])) {
            $this->resource = $opt[self::class][$this->protocol];
        } else {
            $this->resource = $this->callInner('fopen', $arguments);
        }

        $meta = $this->callInner('stream_get_meta_data', [$this->resource]);

        $this->seekable = $meta['seekable'];
        $this->readable = (bool)preg_match('/r|a\+|ab\+|w\+|wb\+|x\+|xb\+|c\+|cb\+/', $meta['mode']);
        $this->writable = (bool)preg_match('/a|w|r\+|rb\+|rw|x|c/', $meta['mode']);

        if ($opened_path !== null) {
            $opened_path = $meta['uri'];
        }

        return is_resource($this->resource);
    }

    /**
     * WrapperInterface method
     *
     * @param  int    $count
     * @return string
     */
    public function stream_read($count)
    {
        return $this->callInner('fread', [$this->resource, $count]);
    }

    /**
     * WrapperInterface method
     *
     * @param  string $path
     * @param  int    $options
     * @return bool
     */
    public function dir_opendir($path, $options)
    {
        return (bool)$this->resource = $this->callInner('opendir', [$path, $this->context]);
    }

    /**
     * WrapperInterface method
     *
     * @return bool
     */
    public function dir_closedir()
    {
        return $this->callInner('closedir', [$this->resource]);
    }

    /**
     * WrapperInterface method
     *
     * @return string|false
     */
    public function dir_readdir()
    {
        return $this->callInner('readdir', [$this->resource]);
    }

    /**
     * WrapperInterface method
     *
     * @return bool
     */
    public function dir_rewinddir()
    {
        return $this->callInner('rewinddir', [$this->resource]);
    }

    /**
     * WrapperInterface method
     *
     * @param  string $path
     * @param  int    $mode
     * @param  int    $options
     * @return bool
     */
    public function mkdir($path, $mode, $options)
    {
        return $this->callInner('mkdir', [$path, $mode, (bool)($options & STREAM_MKDIR_RECURSIVE), $this->context]);
    }

    /**
     * WrapperInterface method
     *
     * @param  string $path_from
     * @param  string $path_to
     * @return bool
     */
    public function rename($path_from, $path_to)
    {
        return $this->callInner('rename', [$path_from, $path_to, $this->context]);
    }

    /**
     * WrapperInterface method
     *
     * @param  string $path
     * @param  int    $options
     * @return bool
     */
    public function rmdir($path, $options)
    {
        return $this->callInner('rmdir', [$path, $this->context]);
    }

    /**
     * WrapperInterface method
     *
     * @param  int           $cast_as
     * @return resource|bool
     */
    public function stream_cast($cast_as)
    {
        return (is_resource($this->resource) ? $this->resource : false);
    }

    /**
     * WrapperInterface method
     *
     * @param  int  $operation
     * @return bool
     */
    public function stream_lock($operation)
    {
        return $this->callInner('flock', [$this->resource, $operation]);
    }

    /**
     * WrapperInterface method
     *
     * @param  string     $path
     * @param  int        $option
     * @param  string|int $value
     * @return bool
     */
    public function stream_metadata($path, $option, $value)
    {
        switch ($option) {
            case STREAM_META_TOUCH:
                return $this->callInner('touch', [$path, $value]);
            case STREAM_META_OWNER_NAME:
            case STREAM_META_OWNER:
                return $this->callInner('chown', [$path, $value]);
            case STREAM_META_GROUP_NAME:
            case STREAM_META_GROUP:
                return $this->callInner('chgrp', [$path, $value]);
            case STREAM_META_ACCESS:
                return $this->callInner('chmod', [$path, $value]);
            default:
                return false;
        }
    }

    /**
     * WrapperInterface method
     *
     * @param  int  $option
     * @param  int  $arg1
     * @param  int  $arg2
     * @return bool
     */
    public function stream_set_option($option, $arg1, $arg2)
    {
        switch ($option) {
            case STREAM_OPTION_BLOCKING:
                return $this->callInner('stream_set_blocking', [$this->resource, $arg1]);
            case STREAM_OPTION_READ_TIMEOUT:
                return $this->callInner('stream_set_timeout', [$this->resource, $arg1, $arg2]);
            case STREAM_OPTION_WRITE_BUFFER:
                return ($this->callInner('stream_set_write_buffer', [$this->resource, $arg2]) === 0);
            default:
                return false;
        }
    }

    /**
     * WrapperInterface method
     *
     * @return array
     */
    public function stream_stat()
    {
        return $this->callInner('fstat', [$this->resource]);
    }

    /**
     * WrapperInterface method
     *
     * @param  int $offset
     * @param  int $whence
     * @return bool
     */
    public function stream_seek($offset, $whence = SEEK_SET)
    {
        return ($this->callInner('fseek', [$this->resource, $offset, $whence]) !== -1);
    }

    /**
     * WrapperInterface method
     *
     * @param  string $data
     * @return int
     */
    public function stream_write($data)
    {
        return $this->callInner('fwrite', [$this->resource, $data]);
    }

    /**
     * WrapperInterface method
     *
     * @param  int $new_size
     * @return bool
     */
    public function stream_truncate($new_size)
    {
        return $this->callInner('ftruncate', [$this->resource, $new_size]);
    }

    /**
     * WrapperInterface method
     *
     * @return int
     */
    public function stream_tell()
    {
        return $this->callInner('ftell', [$this->resource]);
    }

    /**
     * WrapperInterface method
     *
     * @param  string $path
     * @return bool
     */
    public function unlink($path)
    {
        return $this->callInner('unlink', [$path, $this->context]);
    }

    /**
     * WrapperInterface method
     *
     * @param  string      $path
     * @param  int         $flags
     * @return array|false
     */
    public function url_stat($path, $flags)
    {
        $function = (($flags & STREAM_URL_STAT_QUIET) ? '@stat' : 'stat');

        return $this->callInner($function, [$path]);
    }

    /**
     * WrapperInterface method
     *
     * @return bool
     */
    public function stream_eof()
    {
        return $this->callInner('feof', [$this->resource]);
    }

    /**
     * WrapperInterface method
     *
     * @return bool
     */
    public function stream_flush()
    {
        return $this->callInner('fflush', [$this->resource]);
    }

    /**
     * WrapperInterface method
     *
     * @return void
     */
    public function stream_close()
    {
        $this->callInner('fclose', [$this->resource]);
    }
}
