<?php

namespace Phiox\Stream\Wrapper;

use Phiox\Stream\WrapperInterface;

class File implements WrapperInterface
{

    /**
     * @var resource Stream context handle
     */
    public $context;

    /**
     * @var resource Stream resource-handle
     */
    protected $resource;

    /**
     * @var bool|string Wrapper protocol name or false
     */
    protected static $registered = false;

    /**
     * Constructor
     *
     * @param resource $stream Stream resource-handle
     */
    public function __construct($stream = null)
    {
        $this->register();

        if ($stream == null) {
            $this->resource = fopen('php://temp', 'wb');
        } else {
            $this->resource = $stream;
        }
    }

    /**
     * Run function on the native PHP stream wrapper.
     *
     * @param  string $function
     * @param  mixed  $arguments
     * @return mixed
     */
    protected function callInner($function, $arguments)
    {
        $arguments = array_shift(func_get_args());
        $silent = $function[0] === '@';
        $function = ltrim($function, '@');

        try {
            $this->unregister();

            if ($silent) {
                $result = @call_user_func_array($function, $arguments);
            } else {
                $result = call_user_func_array($function, $arguments);
            }
        } finally {
            $this->register();
        }

        return $result;
    }

    /**
     * Unregister stream wrapper
     *
     * @return void
     */
    protected function unregister()
    {
        if (self::$registered) {
            stream_wrapper_restore(self::$registered);

            self::$registered = false;
        }
    }

    /**
     * Register stream wrapper
     *
     * @param  string $protocol
     * @return void
     */
    protected function register($protocol = 'file')
    {
        if (!self::$registered) {
            self::$registered = $protocol;

            stream_wrapper_unregister($protocol);
            stream_wrapper_register($protocol, static::class);
        }
    }

    /**
     * WrapperInterface method
     *
     * @return bool
     */
    public function dir_closedir()
    {
        if (!is_resource($this->resource)) {
            return false;
        }

        $this->callInner('closedir', $this->resource);

        return !is_resource($this->resource);
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
        $this->resource = $this->callInner('opendir', $path, $this->context);

        return is_resource($this->resource);
    }

    /**
     * WrapperInterface method
     * @return string|false
     */
    public function dir_readdir()
    {
        return $this->callInner('readdir', $this->resource);
    }

    /**
     * WrapperInterface method
     *
     * @return bool
     */
    public function dir_rewinddir()
    {
        if (!is_resource($this->resource)) {
            return false;
        }

        $this->callInner('rewinddir', $this->resource);

        return is_resource($this->resource);
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
        return $this->callInner('mkdir', $path, $mode, (bool)($options & STREAM_MKDIR_RECURSIVE), $this->context);
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
        return $this->callInner('rename', $path_from, $path_to, $this->context);
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
        return $this->callInner('rmdir', $path, $this->context);
    }

    /**
     * WrapperInterface method
     *
     * @param  int           $cast_as
     * @return resource|bool
     */
    public function stream_cast($cast_as)
    {
        return false;
    }

    /**
     * WrapperInterface method
     *
     * @return void
     */
    public function stream_close()
    {
        $this->callInner('fclose', $this->resource);
    }

    /**
     * WrapperInterface method
     *
     * @return bool
     */
    public function stream_eof()
    {
        return $this->callInner('feof', $this->resource);
    }

    /**
     * WrapperInterface method
     *
     * @return bool
     */
    public function stream_flush()
    {
        return $this->callInner('fflush', $this->resource);
    }

    /**
     * WrapperInterface method
     *
     * @param  int  $operation
     * @return bool
     */
    public function stream_lock($operation)
    {
        return $this->callInner('flock', $this->resource, $operation);
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
                return $this->callInner('touch', $path, $value);
            case STREAM_META_OWNER_NAME:
            case STREAM_META_OWNER:
                return $this->callInner('chown', $path, $value);
            case STREAM_META_GROUP_NAME:
            case STREAM_META_GROUP:
                return $this->callInner('chgrp', $path, $value);
            case STREAM_META_ACCESS:
                return $this->callInner('chmod', $path, $value);
            default:
                return false;
        }
    }

    /**
     * WrapperInterface method
     *
     * @param  string $path
     * @param  string $mode
     * @param  int $options
     * @param  string|null $opened_path
     * @return bool
     */
    public function stream_open($path, $mode, $options, &$opened_path = null)
    {
        $arguments = [
            $path,
            $mode,
            (bool)($options & STREAM_USE_PATH)
        ];

        if (!($options & static::STREAM_OPEN_FOR_INCLUDE)) {
            $arguments[] = $this->context;
        }

        $this->resource = $this->callInner('fopen', $arguments);

        if (!is_resource($this->resource)) {
            return false;
        }

        if ($opened_path !== null) {
            $meta = stream_get_meta_data($this->resource);
            $opened_path = $meta['uri'];
        }

        return true;
    }

    /**
     * WrapperInterface method
     *
     * @param  int    $count
     * @return string
     */
    public function stream_read($count)
    {
        return $this->callInner('fread', $this->resource, $count);
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
        return $this->callInner('fseek', $this->resource, $offset, $whence) !== -1;
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
                return $this->callInner('stream_set_blocking', $this->resource, $arg1);
            case STREAM_OPTION_READ_TIMEOUT:
                return $this->callInner('stream_set_timeout', $this->resource, $arg1, $arg2);
            case STREAM_OPTION_WRITE_BUFFER:
                return $this->callInner('stream_set_write_buffer', $this->resource, $arg2) === 0;
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
        return $this->callInner('fstat', $this->resource);
    }

    /**
     * WrapperInterface method
     *
     * @return int
     */
    public function stream_tell()
    {
        return $this->callInner('ftell', $this->resource);
    }

    /**
     * WrapperInterface method
     *
     * @param  int $new_size
     * @return bool
     */
    public function stream_truncate($new_size)
    {
        return $this->callInner('ftruncate', $this->resource, $new_size);
    }

    /**
     * WrapperInterface method
     *
     * @param  string $data
     * @return int
     */
    public function stream_write($data)
    {
        return $this->callInner('fwrite', $this->resource, $data);
    }

    /**
     * WrapperInterface method
     *
     * @param  string $path
     * @return bool
     */
    public function unlink($path)
    {
        return $this->callInner('unlink', $path, $this->context);
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
        $function = $flags & STREAM_URL_STAT_QUIET ? '@stat' : 'stat';

        return $this->callInner($function, $path);
    }
}
