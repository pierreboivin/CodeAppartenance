<?php

class SculpinKernel extends \Sculpin\Bundle\SculpinBundle\HttpKernel\AbstractKernel
{
    protected function getAdditionalSculpinBundles(): array
    {
        return [
            'Janbuecker\Sculpin\Bundle\MetaNavigationBundle\SculpinMetaNavigationBundle',
            'PB\Sculpin\Bundle\MetaNavigationBundle\SculpinBreadcrumbNavigationBundle',
        ];
    }
}
