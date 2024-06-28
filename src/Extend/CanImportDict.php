<?php

namespace Slowlyo\OwlDict\Extend;

use Illuminate\Support\Arr;
use Slowlyo\OwlAdmin\Admin;
use Illuminate\Support\Facades\Validator;
use Slowlyo\OwlDict\Models\AdminDict;

/**
 * @property \Symfony\Component\Console\Output\OutputInterface $output
 */
trait CanImportDict
{

    protected array $dictValidationRules = [
        'parent'   => 'nullable',
        'key'    => 'required',
        'value'    => 'required',
    ];

    /**
     * 获取字典节点.
     *
     * @return array
     */
    protected function dict()
    {
        return $this->dict;
    }

    /**
     * 添加字典.
     *
     * @param array $dict
     *
     * @throws \Exception
     */
    protected function addDict(array $dict = [])
    {
        $dict = $dict ?: $this->dict();

        if (!Arr::isAssoc($dict)) {
            foreach ($dict as $v) {
                $this->addDict($v);
            }

            return;
        }

        if (!$this->validateDict($dict)) {
            return;
        }

        AdminDict::query()->insert([
            'parent_id' => $this->getParentDictId($dict['parent'] ?? 0),
            'key'     => $dict['key'],
            'value'     => $dict['value'],
            'extension' => $this->getName(),
            'created_at'=>date('Y-m-d H:i:s'),
            'updated_at'=>date('Y-m-d H:i:s'),
        ]);
    }

    /**
     * 刷新菜单.
     *
     * @throws \Exception
     */
    protected function refreshDict()
    {
        $this->flushDict();

        $this->addDict();
    }

    /**
     * 根据名称获取菜单ID.
     *
     * @param int|string $parent
     *
     * @return int
     */
    protected function getParentDictId($parent)
    {

        return AdminDict::query()
            ->where('key', $parent)
            ->where('extension', $this->getName())
            ->value('id') ?: 0;
    }

    /**
     * 删除字典.
     */
    protected function flushDict()
    {
        AdminDict::query()
            ->where('extension', $this->getName())
            ->delete();
    }

    /**
     * 验证菜单字段格式是否正确.
     *
     * @param array $dict
     *
     * @return bool
     *
     * @throws \Exception
     */
    public function validateDict(array $dict)
    {
        /** @var \Illuminate\Validation\Validator $validator */
        $validator = Validator::make($dict, $this->dictValidationRules);

        if ($validator->passes()) {
            return true;
        }

        return false;
    }
}
