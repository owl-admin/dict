<?php

namespace Slowlyo\OwlDict\Http\Controllers;

use Slowlyo\OwlAdmin\Renderers\Dialog;
use Slowlyo\OwlAdmin\Renderers\CRUDTable;
use Slowlyo\OwlAdmin\Renderers\Operation;
use Slowlyo\OwlAdmin\Renderers\TableColumn;
use Slowlyo\OwlAdmin\Renderers\TextControl;
use Slowlyo\OwlDict\OwlDictServiceProvider;
use Slowlyo\OwlAdmin\Renderers\DialogAction;
use Slowlyo\OwlAdmin\Renderers\SwitchControl;
use Slowlyo\OwlAdmin\Renderers\NumberControl;
use Slowlyo\OwlAdmin\Renderers\SelectControl;
use Slowlyo\OwlAdmin\Renderers\VanillaAction;
use Slowlyo\OwlDict\Services\AdminDictService;
use Slowlyo\OwlAdmin\Controllers\AdminController;

/**
 * @property \Slowlyo\OwlAdmin\Services\AdminService|AdminDictService $service
 */
class OwlDictController extends AdminController
{
    protected string $serviceName = AdminDictService::class;

    protected string $queryPath = 'admin_dict';

    public function __construct()
    {
        parent::__construct();

        $this->pageTitle = $this->trans('page_title');
    }

    /**
     * @return \Illuminate\Http\JsonResponse|\Illuminate\Http\Resources\Json\JsonResource
     * @throws \Psr\Container\ContainerExceptionInterface
     * @throws \Psr\Container\NotFoundExceptionInterface
     */
    public function index()
    {
        if ($this->actionOfGetData()) {
            return $this->response()->success($this->service->list());
        }

        return $this->response()->success($this->list());
    }

    /**
     * @return \Slowlyo\OwlAdmin\Renderers\Page
     * @throws \Psr\Container\ContainerExceptionInterface
     * @throws \Psr\Container\NotFoundExceptionInterface
     */
    public function list()
    {
        $createButton = $this->createButton(true);

        if (OwlDictServiceProvider::setting('disabled_dict_create')) {
            $createButton = '';
        }

        $rowAction = Operation::make()->label(__('admin.actions'))->buttons([
            $this->rowEditButton(true),
            $this->rowDeleteButton(),
        ])->set('width', 240);

        if (OwlDictServiceProvider::setting('disabled_dict_delete')) {
            $rowAction = Operation::make()->label(__('admin.actions'))->buttons([
                $this->rowEditButton(true),
            ])->set('width', 120);
        }

        $dictTypeButton = $this->dictForm();

        if (OwlDictServiceProvider::setting('disabled_dict_type')) {
            $dictTypeButton = '';
        }

        $crud = $this->baseCRUD()
            ->headerToolbar([
                $createButton,
                'bulkActions',
                $dictTypeButton,
                amis('reload')->align('right'),
                amis('filter-toggler')->align('right'),
            ])
            ->filter(
                $this->baseFilter()->body([
                    SelectControl::make()
                        ->name('parent_id')
                        ->label($this->trans('type'))
                        ->source(admin_url('/admin_dict/dict_type_options'))
                        ->valueField('id')
                        ->size('md')
                        ->clearable(true)
                        ->labelField('value'),
                    TextControl::make()->name('key')->label($this->trans('field.key'))->size('md'),
                    TextControl::make()->name('value')->label($this->trans('field.value'))->size('md'),
                    SelectControl::make()
                        ->name('enabled')
                        ->label($this->trans('field.enabled'))
                        ->size('md')
                        ->clearable(true)
                        ->options([
                            ['label' => $this->trans('yes'), 'value' => 1],
                            ['label' => $this->trans('no'), 'value' => 0],
                        ]),
                ])
            )
            ->columns([
                TableColumn::make()
                    ->name('dict_type.value')
                    ->label($this->trans('type'))
                    ->type('tag')
                    ->set('color', 'active'),
                TableColumn::make()->name('key')->label($this->trans('field.key')),
                TableColumn::make()->name('value')->label($this->trans('field.value')),
                TableColumn::make()->name('enabled')->label($this->trans('field.enabled'))->type('status')->width(120),
                TableColumn::make()->name('sort')->label($this->trans('field.sort'))->width(120),
                TableColumn::make()->name('created_at')->label(__('admin.created_at'))->width(120),
                TableColumn::make()->name('updated_at')->label(__('admin.updated_at'))->width(120),
                $rowAction,
            ]);

        return $this->baseList($crud);
    }


    public function form()
    {
        return $this->baseForm()->id('dict_item_form')->data([
            'enabled' => true,
            'sort'    => 0,
        ])->body([
            SelectControl::make()
                ->name('parent_id')
                ->label($this->trans('type'))
                ->source(admin_url('/admin_dict/dict_type_options'))
                ->clearable(true)
                ->required(true)
                ->valueField('id')
                ->labelField('value'),
            TextControl::make()->name('value')->label($this->trans('field.value'))->required(true)->maxLength(255),
            TextControl::make()->name('key')->label($this->trans('field.key'))->required(true)->maxLength(255)->addOn(
                VanillaAction::make()->label($this->trans('random'))->icon('fa-solid fa-shuffle')->onEvent([
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
            NumberControl::make()
                ->name('sort')
                ->label($this->trans('field.sort'))
                ->displayMode('enhance')
                ->min(0)
                ->max(9999)
                ->description($this->trans('sort_description')),
            SwitchControl::make()->name('enabled')->label($this->trans('field.enabled')),
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

    public function dictForm()
    {
        $form = $this->baseForm()->api($this->getStorePath())->data([
            'enabled' => true,
            'sort'    => 0,
        ])->body([
            TextControl::make()->name('value')->label($this->trans('field.value'))->required(true)->maxLength(255),
            TextControl::make()->name('key')->label($this->trans('field.key'))->required(true)->maxLength(255),
            SwitchControl::make()->name('enabled')->label($this->trans('field.enabled')),
        ]);

        $createButton = DialogAction::make()
            ->dialog(Dialog::make()->title(__('admin.create'))->body($form))
            ->label(__('admin.create'))
            ->icon('fa fa-add')
            ->level('primary');

        $editForm = (clone $form)->api($this->getUpdatePath('$id'))->initApi($this->getEditGetDataPath('$id'));

        $editButton = DialogAction::make()
            ->dialog(Dialog::make()->title(__('admin.edit'))->body($editForm))
            ->label(__('admin.edit'))
            ->icon('fa-regular fa-pen-to-square')
            ->level('link');

        return DialogAction::make()->label($this->trans('dict_type'))->dialog(
            Dialog::make()->title($this->trans('dict_type'))->size('lg')->actions([])->body(
                CRUDTable::make()
                    ->perPage(20)
                    ->affixHeader(false)
                    ->filterTogglable(true)
                    ->filterDefaultVisible(false)
                    ->bulkActions([$this->bulkDeleteButton()])
                    ->perPageAvailable([10, 20, 30, 50, 100, 200])
                    ->footerToolbar(['switch-per-page', 'statistics', 'pagination'])
                    ->api($this->getListGetDataPath() . '&_type=1')
                    ->headerToolbar([
                        $createButton,
                        'bulkActions',
                        amis('reload')->align('right'),
                        amis('filter-toggler')->align('right'),
                    ])
                    ->filter(
                        $this->baseFilter()->data(['_type' => 1])->body([
                            TextControl::make()->name('type_key')->label($this->trans('field.type_key'))->size('md'),
                            TextControl::make()->name('type_value')->label($this->trans('field.value'))->size('md'),
                            SelectControl::make()
                                ->name('type_enabled')
                                ->label($this->trans('field.enabled'))
                                ->size('md')
                                ->clearable(true)
                                ->options([
                                    '1' => $this->trans('yes'),
                                    '0' => $this->trans('no'),
                                ]),
                        ])
                    )
                    ->columns([
                        TableColumn::make()->name('value')->label($this->trans('field.value')),
                        TableColumn::make()->name('key')->label($this->trans('field.type_key')),
                        TableColumn::make()
                            ->name('enabled')
                            ->label($this->trans('field.enabled'))
                            ->type('status')
                            ->width(120),
                        TableColumn::make()->name('created_at')->label(__('admin.created_at'))->width(120),
                        TableColumn::make()->name('updated_at')->label(__('admin.updated_at'))->width(120),
                        Operation::make()->label(__('admin.actions'))->buttons([
                            $editButton,
                            $this->rowDeleteButton(),
                        ])->set('width', 240),
                    ])
            )
        );
    }

    private function trans($key)
    {
        return OwlDictServiceProvider::trans('admin-dict.' . $key);
    }
}
