<?php

namespace Ip\SeoBundle\DependencyInjection;

use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\HttpKernel\DependencyInjection\Extension;
use Symfony\Component\DependencyInjection\Loader;

/**
 * This is the class that loads and manages your bundle configuration.
 *
 * @link http://symfony.com/doc/current/cookbook/bundles/extension.html
 */
class IpSeoExtension extends Extension
{
    /**
     * {@inheritdoc}
     */
    public function load(array $configs, ContainerBuilder $container)
    {
        $configuration = new Configuration();
        $config = $this->processConfiguration($configuration, $configs);
        $container->setParameter('ip_seo', $config);
        $loader = new Loader\YamlFileLoader($container, new FileLocator(__DIR__ . '/../Resources/config'));
        $loader->load('services.yml');
        foreach ($config as $key => $value) {
            $container->setParameter('ip_seo.' . $key, $value);
        }
        $this->registerWidget($container);
    }

    protected function registerWidget(ContainerBuilder $container)
    {
        $templatingEngines = $container->getParameter('templating.engines');
        if (in_array('twig', $templatingEngines)) {
            $formRessource = 'IpSeoBundle:Form:ip_seo__widget.html.twig';
            $container->setParameter('twig.form.resources', array_merge(
                $container->getParameter('twig.form.resources'),
                array($formRessource)
            ));
        }
    }
}
