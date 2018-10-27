<?php

declare(strict_types=1);

namespace Bolt\Content;

use Bolt\Configuration\Config;
use Bolt\Entity\Content;
use Bolt\Repository\ContentRepository;
use Knp\Menu\FactoryInterface;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Stopwatch\Stopwatch;

class MenuBuilder
{
    /** @var FactoryInterface */
    private $factory;

    /** @var Config */
    private $config;

    /** @var Stopwatch */
    private $stopwatch;

    /** @var ContentRepository */
    private $content;

    /** @var UrlGeneratorInterface */
    private $urlGenerator;

    /**
     * MenuBuilder constructor.
     *
     * @param FactoryInterface      $factory
     * @param Config                $config
     * @param Stopwatch             $stopwatch
     * @param ContentRepository     $content
     * @param UrlGeneratorInterface $urlGenerator
     */
    public function __construct(FactoryInterface $factory, Config $config, Stopwatch $stopwatch, ContentRepository $content, UrlGeneratorInterface $urlGenerator)
    {
        $this->config = $config;
        $this->factory = $factory;
        $this->stopwatch = $stopwatch;
        $this->content = $content;
        $this->urlGenerator = $urlGenerator;
    }

    public function createSidebarMenu()
    {
        $this->stopwatch->start('bolt.sidebar');

        $menu = $this->factory->createItem('root');

        $menu->addChild('Dashboard', ['uri' => 'homepage', 'extras' => [
            'name' => 'Dashboard',
            'icon' => 'fa-tachometer-alt',
            'link' => '/bolt/',
        ]]);

        $menu->addChild('Content', ['uri' => 'content', 'extras' => [
            'name' => 'Content',
            'type' => 'separator',
            'icon' => 'fa-file',
        ]]);

        $contenttypes = $this->config->get('contenttypes');

        foreach ($contenttypes as $contenttype) {
            $menu->addChild($contenttype['slug'], ['uri' => 'homepage', 'extras' => [
                'name' => $contenttype['name'],
                'singular_name' => $contenttype['singular_name'],
                'slug' => $contenttype['slug'],
                'singular_slug' => $contenttype['singular_slug'],
                'icon' => $contenttype['icon_many'],
                'link' => $this->urlGenerator->generate('bolt_contentlisting', ['contenttype' => $contenttype['slug']]),
                'link_new' => $this->urlGenerator->generate('bolt_edit_record', ['id' => $contenttype['slug']]),
                'contenttype' => $contenttype['slug'],
                'singleton' => $contenttype['singleton'],
                'active' => $contenttype['slug'] === 'pages' ? true : false,
                'submenu' => $this->getLatestRecords($contenttype['slug']),
            ]]);
        }

        $menu->addChild('Settings', ['uri' => 'settings', 'extras' => [
            'name' => 'Settings',
            'type' => 'separator',
            'icon' => 'fa-wrench',
        ]]);

        // Configuration submenu

        $menu->addChild('Configuration', ['uri' => 'configuration', 'extras' => [
            'name' => 'Configuration',
            'icon' => 'fa-flag',
            'link' => '/bolt/finder/config',
        ]]);

        $menu['Configuration']->addChild('Users &amp; Permissions', ['uri' => 'users', 'extras' => [
            'name' => 'Users &amp; Permissions',
            'icon' => 'fa-group',
            'link' => '/bolt/finder/config',
        ]]);

        $menu['Configuration']->addChild('Main configuration', ['uri' => 'config', 'extras' => [
            'name' => 'Main configuration',
            'icon' => 'fa-cog',
            'link' => '/bolt/editfile/config?file=/bolt/config.yaml',
        ]]);

        $menu['Configuration']->addChild('ContentTypes', ['uri' => 'contenttypes', 'extras' => [
            'name' => 'ContentTypes',
            'icon' => 'fa-paint-brush',
            'link' => '/bolt/editfile/config?file=/bolt/contenttypes.yml',
        ]]);

        $menu['Configuration']->addChild('Taxonomy', ['uri' => 'taxonomy', 'extras' => [
            'name' => 'Taxonomy',
            'icon' => 'fa-tags',
            'link' => '/bolt/editfile/config?file=/bolt/taxonomy.yml',
        ]]);

        $menu['Configuration']->addChild('Menu set up', ['uri' => 'menusetup', 'extras' => [
            'name' => 'Menu set up',
            'type' => 'separator',
            'icon' => 'fa-list',
            'link' => '/bolt/editfile/config?file=/bolt/menu.yml',
        ]]);

        $menu['Configuration']->addChild('Routing set up', ['uri' => 'routing', 'extras' => [
            'name' => 'Routing set up',
            'icon' => 'fa-random',
            'link' => '/bolt/editfile/config?file=/bolt/routing.yml',
        ]]);

        $menu['Configuration']->addChild('Check database', ['uri' => 'database', 'extras' => [
            'name' => 'Check database',
            'type' => 'separator',
            'icon' => 'fa-database',
            'link' => '/bolt/finder/config',
        ]]);

        $menu['Configuration']->addChild('Clear the cache', ['uri' => 'cache', 'extras' => [
            'name' => 'Clear the cache',
            'icon' => 'fa-eraser',
            'link' => '/bolt/finder/config',
        ]]);

        $menu['Configuration']->addChild('Change Log', ['uri' => 'else', 'extras' => [
            'name' => 'Change Log',
            'icon' => 'fa-archive',
            'link' => '/bolt/finder/config',
        ]]);

        $menu['Configuration']->addChild('System Log', ['uri' => 'else', 'extras' => [
            'name' => 'System Log',
            'icon' => 'fa-archive',
            'link' => '/bolt/finder/config',
        ]]);

        $menu['Configuration']->addChild('Set-up checks', ['uri' => 'else', 'extras' => [
            'name' => 'Set-up checks',
            'icon' => 'fa-support',
            'link' => '/bolt/finder/config',
        ]]);

        $menu['Configuration']->addChild('Translations: Messages', ['uri' => 'else', 'extras' => [
            'name' => 'Translations: Messages',
            'type' => 'separator',
            'icon' => 'fa-flag',
            'link' => '/bolt/finder/config',
        ]]);

        // File Management submenu
        $menu->addChild('File Management', ['uri' => 'content-files', 'extras' => [
            'name' => 'File Management',
            'icon' => 'fa-flag',
            'link' => '/bolt/finder/files',
        ]]);

        $menu['File Management']->addChild('Uploaded files', ['uri' => 'content-files', 'extras' => [
            'name' => 'Uploaded files',
            'icon' => 'fa-folder-open-o',
            'link' => '/bolt/finder/files',
        ]]);

        $menu['File Management']->addChild('View/edit Templates', ['uri' => 'theme-files', 'extras' => [
            'name' => 'View/edit Templates',
            'icon' => 'fa-desktop',
            'link' => '/bolt/finder/themes',
        ]]);

        $menu->addChild('Extensions', ['uri' => 'extensions', 'extras' => [
            'name' => 'Extensions',
            'icon' => 'fa-cubes',
            'link' => '/bolt/extensions',
        ]]);

        $this->stopwatch->stop('bolt.sidebar');

        return $menu;
    }

    private function getLatestRecords($slug)
    {
        /** @var ContentType $ct */
        $contenttype = ContentTypeFactory::get($slug, $this->config->get('contenttypes'));

        /** @var Content $records */
        $records = $this->content->findLatest($contenttype, 5);

        $result = [];

        /** @var Content $record */
        foreach ($records as $record) {
            $result[] = [
                'id' => $record->getId(),
                'name' => $record->magicTitle(),
                'link' => $record->magicLink(),
                'editlink' => $record->magicEditLink(),
                'icon' => $record->getDefinition()->get('icon_one'),
            ];
        }

        return $result;
    }

    public function getMenu()
    {
        $menu = $this->createSidebarMenu()->getChildren();

        $menuData = [];

        foreach ($menu as $child) {
            $submenu = [];

            if ($child->hasChildren()) {
                foreach ($child->getChildren() as $submenuChild) {
                    $submenu[] = [
                        'name' => $submenuChild->getExtra('name') ?: $submenuChild->getLabel(),
                        'singular_name' => $submenuChild->getExtra('singular_name'),
                        'slug' => $submenuChild->getExtra('slug'),
                        'singular_slug' => $submenuChild->getExtra('singular_slug'),
                        'icon' => $submenuChild->getExtra('icon'),
                        'link' => $submenuChild->getExtra('link'),
                        'link_new' => $submenuChild->getExtra('link_new'),
                        'contenttype' => $submenuChild->getExtra('contenttype'),
                        'singleton' => $submenuChild->getExtra('singleton'),
                        'type' => $submenuChild->getExtra('type'),
                        'active' => $submenuChild->getExtra('active'),
                    ];
                }
            } else {
                $submenu = $child->getExtra('submenu');
            }

            $menuData[] = [
                'name' => $child->getExtra('name') ?: $child->getLabel(),
                'singular_name' => $child->getExtra('singular_name'),
                'slug' => $child->getExtra('slug'),
                'singular_slug' => $child->getExtra('singular_slug'),
                'icon' => $child->getExtra('icon'),
                'link' => $child->getExtra('link'),
                'link_new' => $child->getExtra('link_new'),
                'contenttype' => $child->getExtra('contenttype'),
                'singleton' => $child->getExtra('singleton'),
                'type' => $child->getExtra('type'),
                'active' => $child->getExtra('active'),
                'submenu' => $submenu,
            ];
        }

        return $menuData;
    }
}
