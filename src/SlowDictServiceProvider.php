<?php

namespace Slowlyo\SlowDict;

use Slowlyo\SlowAdmin\Extend\ServiceProvider;
use Slowlyo\SlowAdmin\Renderers\SwitchControl;

class SlowDictServiceProvider extends ServiceProvider
{
    protected $menu = [
        [
            'title' => '数据字典',
            'url'   => '/admin_dict',
            'icon'  => 'fluent-mdl2:dictionary',
        ],
    ];

    public function register()
    {
        $this->app->singleton('admin.dict', AdminDict::class);
    }

    public function settingForm()
    {
        return $this->baseSettingForm()->body([
            SwitchControl::make()->name('disabled_dict_type')->label('屏蔽数据字典类型管理'),
            SwitchControl::make()->name('disabled_dict_create')->label('屏蔽数据字典创建'),
            SwitchControl::make()->name('disabled_dict_delete')->label('屏蔽数据字典删除'),
        ]);
    }
}
