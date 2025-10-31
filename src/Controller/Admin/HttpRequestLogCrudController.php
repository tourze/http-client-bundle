<?php

declare(strict_types=1);

namespace HttpClientBundle\Controller\Admin;

use EasyCorp\Bundle\EasyAdminBundle\Attribute\AdminCrud;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Config\Filters;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\AssociationField;
use EasyCorp\Bundle\EasyAdminBundle\Field\CodeEditorField;
use EasyCorp\Bundle\EasyAdminBundle\Field\DateTimeField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IdField;
use EasyCorp\Bundle\EasyAdminBundle\Field\NumberField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextareaField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;
use EasyCorp\Bundle\EasyAdminBundle\Filter\DateTimeFilter;
use EasyCorp\Bundle\EasyAdminBundle\Filter\TextFilter;
use HttpClientBundle\Entity\HttpRequestLog;

#[AdminCrud(
    routePath: '/http-client/http-request-log',
    routeName: 'http_client_http_request_log'
)]
final class HttpRequestLogCrudController extends AbstractCrudController
{
    public static function getEntityFqcn(): string
    {
        return HttpRequestLog::class;
    }

    public function configureCrud(Crud $crud): Crud
    {
        return $crud
            ->setEntityLabelInSingular('HTTP请求日志')
            ->setEntityLabelInPlural('HTTP请求日志管理')
            ->setPageTitle(Crud::PAGE_INDEX, 'HTTP请求日志列表')
            ->setPageTitle(Crud::PAGE_NEW, '新建HTTP请求日志')
            ->setPageTitle(Crud::PAGE_EDIT, '编辑HTTP请求日志')
            ->setPageTitle(Crud::PAGE_DETAIL, 'HTTP请求日志详情')
            ->setDefaultSort(['createTime' => 'DESC'])
            ->setSearchFields(['requestUrl', 'method', 'response'])
            ->showEntityActionsInlined()
            ->setFormThemes(['@EasyAdmin/crud/form_theme.html.twig'])
        ;
    }

    public function configureFields(string $pageName): iterable
    {
        yield IdField::new('id', 'ID')
            ->onlyOnIndex()
        ;

        yield TextField::new('requestUrl', '请求链接')
            ->setColumns('col-md-12')
            ->setRequired(true)
            ->setHelp('HTTP请求的完整URL')
        ;

        yield TextField::new('method', '请求方式')
            ->setColumns('col-md-6')
            ->setHelp('HTTP请求方法：GET, POST, PUT, DELETE等')
        ;

        yield NumberField::new('stopwatchDuration', '执行时长')
            ->setColumns('col-md-6')
            ->setNumDecimals(2)
            ->setHelp('请求执行时长（秒）')
            ->hideOnForm()
        ;

        yield TextareaField::new('content', '请求内容')
            ->setColumns('col-md-12')
            ->setNumOfRows(5)
            ->onlyOnForms()
            ->setHelp('HTTP请求的请求体内容')
        ;

        yield CodeEditorField::new('content', '请求内容')
            ->setLanguage('javascript')
            ->onlyOnDetail()
            ->setHelp('HTTP请求的请求体内容')
        ;

        yield TextareaField::new('response', '响应内容')
            ->setColumns('col-md-12')
            ->setNumOfRows(8)
            ->onlyOnForms()
            ->setHelp('HTTP请求的响应内容')
        ;

        yield CodeEditorField::new('response', '响应内容')
            ->setLanguage('javascript')
            ->onlyOnDetail()
            ->setHelp('HTTP请求的响应内容')
        ;

        yield TextareaField::new('exception', '异常信息')
            ->setColumns('col-md-12')
            ->setNumOfRows(5)
            ->onlyOnForms()
            ->setHelp('请求过程中发生的异常信息')
        ;

        yield CodeEditorField::new('exception', '异常信息')
            ->setLanguage('shell')
            ->onlyOnDetail()
            ->setHelp('请求过程中发生的异常信息')
        ;

        yield CodeEditorField::new('requestOptions', '请求选项')
            ->setLanguage('javascript')
            ->onlyOnDetail()
            ->setHelp('HTTP请求的原始选项参数')
        ;

        yield TextField::new('renderStatus', '状态')
            ->onlyOnIndex()
            ->setHelp('请求执行状态')
        ;

        yield TextField::new('createdFromIp', '创建IP')
            ->onlyOnDetail()
        ;

        yield TextField::new('createdFromUa', '创建UA')
            ->onlyOnDetail()
        ;

        yield DateTimeField::new('createTime', '创建时间')
            ->hideOnForm()
            ->setFormat('yyyy-MM-dd HH:mm:ss')
        ;

        yield AssociationField::new('createdBy', '创建者')
            ->onlyOnDetail()
        ;
    }

    public function configureFilters(Filters $filters): Filters
    {
        return $filters
            ->add(TextFilter::new('requestUrl', '请求链接'))
            ->add(TextFilter::new('method', '请求方式'))
            ->add(TextFilter::new('response', '响应内容'))
            ->add(TextFilter::new('exception', '异常信息'))
            ->add(DateTimeFilter::new('createTime', '创建时间'))
        ;
    }
}
