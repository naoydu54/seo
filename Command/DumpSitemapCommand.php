<?php

namespace Ip\SeoBundle\Command;

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Finder\Finder;
use Symfony\Component\Finder\SplFileInfo;
use Symfony\Component\Routing\RequestContext;
use Symfony\Component\Routing\Router;
use Symfony\Component\Serializer\Encoder\JsonEncoder;
use Symfony\Component\Serializer\Encoder\XmlEncoder;
use Symfony\Component\Serializer\Normalizer\ObjectNormalizer;
use Symfony\Component\Serializer\Serializer;

class DumpSitemapCommand extends ContainerAwareCommand
{
    /** @var  \DateTime */
    private $time = null;

    const DATEFORMAT = 'Y-m-d';
    const TIMEFROMFILE = '*.lock';

    private $locale = 'fr';

    protected function configure()
    {
        $this
            ->setName('ip:seo:dump-sitemap')
            ->setDescription('Créé le sitemap du site internet')
            ->addOption(
                'debug',
                null,
                InputOption::VALUE_NONE,
                'Debug'
            );
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $debug = $input->getOption('debug');

        $io = null;
        if ($debug) {
            $io = new SymfonyStyle($input, $output);
            $io->title('Creating sitemap.xml');
        }
        $container = $this->getContainer();

        $targetDir = $container->getParameter('ip_seo.sitemap_location');
        $routes = $container->get('router')->getRouteCollection()->all();
        $em = $container->get('doctrine')->getManager();


        $fs = new Filesystem();

        $urls = array();

        foreach ($routes as $name => $object) {
            if ($object->getOption('sitemap')) {
                if ($debug) {
                    $io->comment($name);
                }
                $params = $object->compile()->getPathVariables();
                $router = $container->get('router');
                $context = $router->getContext();
                $context->setHost($container->getParameter('ip_seo.host'));
                $context->setScheme($container->getParameter('ip_seo.scheme'));

                if (empty($params)) {
                    $urls[] = array(
                        'loc' => $this->getFullUrl($context, $router, $name),
                        'lastmod' => $this->getDateMod(),
                        'changefreq' => $container->getParameter('ip_seo.change_freq'),
                        'priority' => 1
                    );
                } else {
                    $namespace = $object->getOption('entity');
                    if (!is_null($namespace)) {
                        $entities = $em->getRepository($namespace)->findAll();
                        foreach ($entities as $entity) {
                            $p = [];
                            foreach ($params as $param) {
                                $e = [];
                                $tabNamespace = explode('\\', $namespace);
                                switch ($param) {
                                    case strtolower(end($tabNamespace)):
                                        $e = [
                                            strtolower(end($tabNamespace)) => $entity->getId()
                                        ];
                                        break;
                                    default:
                                        $methodName = 'get' . ucfirst($param);
                                        // Method Name Translation
                                        $mnt = 'get' . ucfirst(end($tabNamespace)) . 'Translations';
                                        if(method_exists($entity ,$methodName)){
                                            $e = [
                                                $param => $entity->{$methodName}()
                                            ];
                                        } else if (method_exists($entity ,$mnt)) {
                                            $e = [
                                                $param => $this->translate($entity->{$mnt}())->{$methodName}()
                                            ];
                                        }
                                        break;
                                }

                                $p = array_merge($p, $e);
                            }

                            if (count($p) === count($params)) {
                                $urls[] = array(
                                    'loc' => $this->getFullUrl($context, $router, $name, $p),
                                    'lastmod' => $this->getDateMod(),
                                    'changefreq' => $container->getParameter('ip_seo.change_freq'),
                                    'priority' => 1
                                );
                            }
                        }
                    }
                }
            }
        }

        $fs->touch($targetDir);
        $fs->dumpFile($targetDir, $this->getContainer()->get('twig')->render('@IpSeo/Seo/sitemap.xml.twig', array(
            'urls' => $urls
        )));

        if ($debug) {
            $io->success('Successfully created');
        }
    }

    private function getDateMod()
    {
        if (!is_null($this->time)) {
            return $this->time->format(self::DATEFORMAT);
        }
        $this->time = new \DateTime();
        $finder = new Finder();

        if (strtoupper(substr(PHP_OS, 0, 3)) === 'WIN') {
            $locks = $finder->files()->in($this->getContainer()->getParameter('kernel.root_dir') . '\..')->name(self::TIMEFROMFILE);
        } else {
            $locks = $finder->files()->in($this->getContainer()->getParameter('kernel.root_dir') . '/..')->name(self::TIMEFROMFILE);
        }
        foreach ($locks as $lock) {
            /** @var SplFileInfo $lock */
            if ($lock->getRelativePath() == "") {
                $this->time = \DateTime::createFromFormat('U', $lock->getMTime());
                return $this->time->format(self::DATEFORMAT);
            }
        }
        return $this->time->format(self::DATEFORMAT);
    }

    private function getFullUrl(RequestContext $context, Router $router, $name, $params = [])
    {
        return $context->getScheme() . '://' . $context->getHost() . $router->generate($name, $params);
    }

    public function translate($translations)
    {
        if (is_object($translations)) {
            $localeTranslation = null;
            $defaultLocaleTranslation = null;

            foreach ($translations as $translation) {
                if ($translation->getLocale()) {
                    if ($translation->getLocale()->getName() == $this->locale) {
                        $localeTranslation = $translation;
                    } elseif ($translation->getLocale()->getName() == $this->locale) {
                        $defaultLocaleTranslation = $translation;
                    }
                }
            }

            if (!is_null($localeTranslation)) {
                return $localeTranslation;
            } elseif (!is_null($defaultLocaleTranslation)) {
                return $defaultLocaleTranslation;
            } else {
                return $translations->first();
            }
        }

        throw new \Exception('$translation must be an object');
    }
}