<?php

namespace PB\Sculpin\Bundle\MetaNavigationBundle;

use Sculpin\Core\Permalink\SourcePermalinkFactoryInterface;
use Sculpin\Core\Sculpin;
use Sculpin\Core\Event\SourceSetEvent;
use Sculpin\Core\Source\SourceSet;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * @author Jan Bücker <jan@buecker.io>
 */
class MenuGenerator implements EventSubscriberInterface
{
    /**
     * @var array
     */
    protected $menu = [];

    /**
     * @var SourcePermalinkFactoryInterface
     */
    private $permalinkFactory;

    /**
     * MenuGenerator constructor.
     * @param SourcePermalinkFactoryInterface $permalinkFactory
     */
    public function __construct(SourcePermalinkFactoryInterface $permalinkFactory)
    {
        $this->permalinkFactory = $permalinkFactory;
    }

    /**
     * {@inheritdoc}
     */
    public static function getSubscribedEvents()
    {
        return [
            Sculpin::EVENT_BEFORE_RUN => 'beforeRun',
        ];
    }

    /**
     * @param SourceSetEvent $sourceSetEvent
     */
    public function beforeRun(SourceSetEvent $sourceSetEvent)
    {
        $sourceSet = $sourceSetEvent->sourceSet();
        $pages = [];

        foreach ($sourceSet->allSources() as $source) {
            /** @var \Sculpin\Core\Source\FileSource $source */

            if ($source->isGenerated() || !$source->canBeFormatted()) {
                // Skip generated sources.
                // Only takes pages that can be formatted (AKA *.md) and skip images, CSS, JS, ...
                continue;
            }

            $menuTitle = $source->data()->get('menu_title');

            if (!$menuTitle) {
                continue;
            }

            $menuOrder = $source->data()->get('menu_order') ?: 1;
            $styling = $source->data()->get('menu_style') ?: null;
            $isChapter = (bool) $source->data()->get('menu_chapter');
            $group = $source->data()->get('group') ?: null;
            $subgroup = null;

            if ($group) {
                $subgroup = $source->data()->get('subgroup') ?: null;
            }

            $permalink = $this->permalinkFactory->create($source);
            $url = rtrim($permalink->relativeUrlPath(), '/') . '/';
            $source->data()->set('url', $url);

            $pages[] = [
                'id' => $group.$subgroup.$menuTitle,
                'menu_title' => $menuTitle,
                'menu_order' => $menuOrder,
                'menu_style' => $styling,
                'menu_chapter' => $isChapter,
                'group' => $group,
                'subgroup' => $subgroup,
                'url' => $url,
                'parent' => $group.$subgroup,
            ];
        }

        $this->breadcrumb = [];

        $urlPages = $pages;
        foreach($pages as $page) {
            $urlGroup = '';
            $urlSubGroup = '';
            foreach($urlPages as $propertiesPage) {
                if ($propertiesPage['menu_title'] === $page['group']) {
                    $urlGroup = $propertiesPage['url'];
                }
                if ($propertiesPage['menu_title'] === $page['subgroup']) {
                    $urlSubGroup = $propertiesPage['url'];
                }
            }
            if ($page['group']) {
                $this->breadcrumb[$page['menu_title']]['group'] = ['menu_title' => $page['group'], 'url' => $urlGroup];
            }
            if ($page['subgroup']) {
                $this->breadcrumb[$page['menu_title']]['subgroup'] = ['menu_title' => $page['subgroup'], 'url' => $urlSubGroup];
            }
        }

        $this->setMenu($sourceSet);
    }

    /**
     * Now that the menu structure has been created, inject it back to the page.
     *
     * @param SourceSet $sourceSet
     * @return void
     */
    protected function setMenu(SourceSet $sourceSet)
    {
        // Second loop to set the menu which was initialized during the first loop
        foreach ($sourceSet->updatedSources() as $source) {
            /** @var \Sculpin\Core\Source\FileSource $source */

            if ($source->isGenerated() || !$source->canBeFormatted()) {
                // Skip generated sources.
                // Only takes pages that can be formatted (AKA *.md)
                continue;
            }

            $source->data()->set('breadcrumb', $this->breadcrumb);
        }
    }
}
