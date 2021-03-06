<?php
/**
 * @see       https://github.com/zendframework/zend-expressive-platesrenderer for the canonical source repository
 * @copyright Copyright (c) 2015-2017 Zend Technologies USA Inc. (http://www.zend.com)
 * @license   https://github.com/zendframework/zend-expressive-platesrenderer/blob/master/LICENSE.md New BSD License
 */

namespace Zend\Expressive\Plates;

use League\Plates\Engine;
use ReflectionProperty;
use Zend\Expressive\Template\ArrayParametersTrait;
use Zend\Expressive\Template\Exception;
use Zend\Expressive\Template\TemplatePath;
use Zend\Expressive\Template\TemplateRendererInterface;

/**
 * Template implementation bridging league/plates
 */
class PlatesRenderer implements TemplateRendererInterface
{
    use ArrayParametersTrait;

    /**
     * @var Engine
     */
    private $template;

    public function __construct(Engine $template = null)
    {
        if (null === $template) {
            $template = $this->createTemplate();
        }
        $this->template = $template;
    }

    /**
     * Render
     *
     * @param string $name
     * @param array|object $params
     * @return string
     */
    public function render($name, $params = [])
    {
        $params = $this->normalizeParams($params);
        return $this->template->render($name, $params);
    }

    /**
     * Add a path for template
     *
     * Multiple calls to this method without a namespace will trigger an
     * E_USER_WARNING and act as a no-op. Plates does not handle non-namespaced
     * folders, only the default directory; overwriting the default directory
     * is likely unintended.
     *
     * @param string $path
     * @param string $namespace
     * @return void
     */
    public function addPath($path, $namespace = null)
    {
        if (! $namespace && ! $this->template->getDirectory()) {
            $this->template->setDirectory($path);
            return;
        }

        if (! $namespace) {
            trigger_error('Cannot add duplicate un-namespaced path in Plates template adapter', E_USER_WARNING);
            return;
        }

        $this->template->addFolder($namespace, $path, true);
    }

    /**
     * Get the template directories
     *
     * @return TemplatePath[]
     */
    public function getPaths()
    {
        $paths = $this->template->getDirectory()
            ? [ $this->getDefaultPath() ]
            : [];

        foreach ($this->getPlatesFolders() as $folder) {
            $paths[] = new TemplatePath($folder->getPath(), $folder->getName());
        }
        return $paths;
    }

    /**
     * Proxies to the Plate Engine's `addData()` method.
     *
     * {@inheritDoc}
     */
    public function addDefaultParam($templateName, $param, $value)
    {
        if (! is_string($templateName) || empty($templateName)) {
            throw new Exception\InvalidArgumentException(sprintf(
                '$templateName must be a non-empty string; received %s',
                is_object($templateName) ? get_class($templateName) : gettype($templateName)
            ));
        }

        if (! is_string($param) || empty($param)) {
            throw new Exception\InvalidArgumentException(sprintf(
                '$param must be a non-empty string; received %s',
                is_object($param) ? get_class($param) : gettype($param)
            ));
        }

        $params = [$param => $value];

        if ($templateName === self::TEMPLATE_ALL) {
            $templateName = null;
        }

        $this->template->addData($params, $templateName);
    }


    /**
     * Create a default Plates engine
     *
     * @params string $path
     * @return Engine
     */
    private function createTemplate()
    {
        return new Engine();
    }

    /**
     * Create and return a TemplatePath representing the default Plates directory.
     *
     * @return TemplatePath
     */
    private function getDefaultPath()
    {
        return new TemplatePath($this->template->getDirectory());
    }

    /**
     * Return the internal array of plates folders.
     *
     * @return \League\Plates\Template\Folder[]
     */
    private function getPlatesFolders()
    {
        $folders = $this->template->getFolders();
        $r = new ReflectionProperty($folders, 'folders');
        $r->setAccessible(true);
        return $r->getValue($folders);
    }
}
