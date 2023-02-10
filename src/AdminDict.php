<?php

namespace Slowlyo\SlowDict;

use Illuminate\Support\Arr;
use Slowlyo\SlowDict\Services\AdminDictService as Service;

class AdminDict
{
    private $data;

    public function key($default = '')
    {
        return Arr::get($this->data, 'key', $default);
    }

    public function value($default = '')
    {
        return Arr::get($this->data, 'value', $default);
    }

    public function all($default = [])
    {
        return $this->data ?: $default;
    }

    public function get($path, $needAllData = false)
    {
        $originData = $needAllData ? Service::make()->getAllData() : Service::make()->getValidData();

        $this->data = Arr::get($originData, $path);

        return $this;
    }

    public function getValue($path, $default = '', $needAllData = true)
    {
        return $this->get($path, $needAllData)->value($default);
    }

    public function getKey($path, $default = '', $needAllData = true)
    {
        return $this->get($path, $needAllData)->key($default);
    }

    public function getAll($path, $default = [], $needAllData = true)
    {
        return $this->get($path, $needAllData)->all($default);
    }
}
