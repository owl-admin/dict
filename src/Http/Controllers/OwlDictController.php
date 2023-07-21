<?php

namespace Slowlyo\OwlDict\Http\Controllers;

use Slowlyo\OwlDict\OwlDictServiceProvider;
use Slowlyo\OwlDict\Services\AdminDictService;
use Slowlyo\OwlAdmin\Controllers\AdminController;

/**
 * @property \Slowlyo\OwlAdmin\Services\AdminService|AdminDictService $service
 */
class OwlDictController extends AdminController
{
    protected string $serviceName = AdminDictService::class;

    public function index()
    {
        if ($this->actionOfGetData()) {
            return $this->response()->success($this->service->list());
        }

        $page = amisMake()->Page()->body([
            amisMake()->Flex()->items([$this->navBar(), $this->list()]),
        ])->css([
            '.cxd-Tree-itemArrowPlaceholder' => ['display' => 'none'],
            '.cxd-Tree-itemLabel'            => ['padding-left' => '0 !important'],
        ]);

        return $this->response()->success($page);
    }

    public function navBar()
    {
        $formItems = [
            amisMake()->TextControl('value', $this->trans('field.value'))->required()->maxLength(255),
            amisMake()->TextControl('key', $this->trans('field.key'))->required()->maxLength(255),
            amisMake()->SwitchControl('enabled', 1)->label($this->trans('field.enabled')),
        ];

        return amisMake()->Card()->className('w-1/4 mr-5 mb-0')->body([
            amisMake()->Flex()->className('mb-4')->justify('space-between')->items([
                amisMake()
                    ->Wrapper()
                    ->size('none')
                    ->body($this->trans('dict_type'))
                    ->className('flex items-center text-md'),
            ]),
            amisMake()
                ->Form()
                ->wrapWithPanel(false)
                ->body(
                    amisMake()
                        ->TreeControl('dict_type')
                        ->id('dict_type_list')
                        ->source('/admin_dict/dict_type_options')
                        ->set('valueField', 'id')
                        ->set('labelField', 'value')
                        ->showIcon(false)
                        ->searchable()
                        ->set('rootCreateTip', __('admin.create') . $this->trans('dict_type'))
                        ->selectFirst()
                        ->creatable($this->dictTypeEnabled())
                        ->addControls($formItems)
                        ->editable($this->dictTypeEnabled())
                        ->editControls(array_merge($formItems, [amisMake()->HiddenControl()->name('id')]))
                        ->removable($this->dictTypeEnabled())
                        ->addApi($this->getStorePath())
                        ->editApi($this->getUpdatePath())
                        ->deleteApi($this->getDeletePath())
                        ->onEvent([
                            'change' => [
                                'actions' => [
                                    [
                                        'actionType' => 'url',
                                        'args'       => ['url' => '/admin_dict?dict_type=${dict_type}'],
                                    ],
                                ],
                            ],
                        ])
                ),
        ]);
    }

    /**
     * @return \Slowlyo\OwlAdmin\Renderers\Page
     * @throws \Psr\Container\ContainerExceptionInterface
     * @throws \Psr\Container\NotFoundExceptionInterface
     */
    public function list()
    {
        $crud = $this->baseCRUD()
            ->api($this->getListGetDataPath() . '&parent_id=${dict_type || ' . $this->service->getFirstId() . '}')
            ->headerToolbar([
                $this->createButton(true)->visible(!OwlDictServiceProvider::setting('disabled_dict_create')),
                'bulkActions',
                amis('reload')->align('right'),
                amis('filter-toggler')->align('right'),
            ])
            ->bulkActions([
                $this->bulkDeleteButton()->visible(!OwlDictServiceProvider::setting('disabled_dict_delete')),
            ])
            ->filter(
                $this->baseFilter()->body([
                    amisMake()->TextControl('key', $this->trans('field.key'))->size('md'),
                    amisMake()->TextControl('value', $this->trans('field.value'))->size('md'),
                    amisMake()->SelectControl('enabled', $this->trans('field.enabled'))
                        ->size('md')
                        ->clearable()
                        ->options([
                            ['label' => $this->trans('yes'), 'value' => 1],
                            ['label' => $this->trans('no'), 'value' => 0],
                        ]),
                ])
            )
            ->columns([
                amisMake()->TableColumn('value', $this->trans('field.value')),
                amisMake()->TableColumn('key', $this->trans('field.key')),
                amisMake()->TableColumn('enabled', $this->trans('field.enabled'))->quickEdit(
                    amisMake()->SwitchControl()->mode('inline')->saveImmediately(true)
                ),
                amisMake()->TableColumn('sort', $this->trans('field.sort'))->width(120),
                amisMake()->TableColumn('created_at', __('admin.created_at'))->width(120),
                $this->rowActions([
                    $this->rowEditButton(true),
                    $this->rowDeleteButton()->visible(!OwlDictServiceProvider::setting('disabled_dict_delete')),
                ])->set('width', 240),
            ]);

        return $this->baseList($crud);
    }

    public function form()
    {
        return $this->baseForm()->id('dict_item_form')->data([
            'enabled' => true,
            'sort'    => 0,
        ])->body([
            amisMake()->SelectControl('parent_id', $this->trans('type'))
                ->source(admin_url('/admin_dict/dict_type_options'))
                ->clearable()
                ->required()
                ->value('${dict_type || ' . $this->service->getFirstId() . '}')
                ->valueField('id')
                ->labelField('value'),
            amisMake()->TextControl('value', $this->trans('field.value'))->required()->maxLength(255),
            amisMake()->TextControl('key', $this->trans('field.key'))->required()->maxLength(255)->addOn(
                amisMake()->VanillaAction()->label($this->trans('random'))->icon('fa-solid fa-shuffle')->onEvent([
                    'click' => [
                        'actions' => [
                            [
                                'actionType'  => 'setValue',
                                'componentId' => 'dict_item_form',
                                'args'        => [
                                    'value' => [
                                        'key' => '${PADSTART(INT(RAND()*1000000000), 9, "0") | base64Encode | lowerCase}',
                                    ],
                                ],
                            ],
                        ],
                    ],
                ])
            ),
            amisMake()->NumberControl('sort', $this->trans('field.sort'))
                ->displayMode('enhance')
                ->min(0)
                ->max(9999)
                ->description($this->trans('sort_description')),
            amisMake()->SwitchControl('enabled', $this->trans('field.enabled')),
        ]);
    }

    public function dictTypeOptions()
    {
        return $this->response()->success($this->service->getDictTypeOptions());
    }

    public function detail($id)
    {
        return $this->baseDetail($id);
    }

    private function trans($key)
    {
        return OwlDictServiceProvider::trans('admin-dict.' . $key);
    }

    private function dictTypeEnabled()
    {
        return !OwlDictServiceProvider::setting('disabled_dict_type');
    }
}
