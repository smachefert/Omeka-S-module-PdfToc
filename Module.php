<?php

namespace PdfToc;

use Laminas\EventManager\Event;
use Laminas\Mvc\Controller\AbstractController;
use Omeka\Module\AbstractModule;
use Omeka\Module\Exception\ModuleCannotInstallException;
use Laminas\EventManager\SharedEventManagerInterface;
use Laminas\ServiceManager\ServiceLocatorInterface;
use Laminas\View\Renderer\PhpRenderer;
use PdfToc\Form\ConfigForm;

class Module extends AbstractModule
{
    public function install(ServiceLocatorInterface $serviceLocator)
    {
        $logger = $serviceLocator->get('Omeka\Logger');
        $t = $serviceLocator->get('MvcTranslator');
        // Don't install if the pdftotext command doesn't exist.
        // See: http://stackoverflow.com/questions/592620/check-if-a-program-exists-from-a-bash-script
        if ((int)shell_exec('hash pdftk 2>&- || echo 1')) {
            $logger->info("pdftk not found");
            throw new ModuleCannotInstallException($t->translate('The pdftk command-line utility '
                . 'is not installed. pdftk must be installed to install this plugin.'));
        }
    }

    public function getConfig()
    {
        return include __DIR__ . '/config/module.config.php';
    }

    public function getConfigForm(PhpRenderer $renderer)
    {
        $services = $this->getServiceLocator();
        $settings = $services->get('Omeka\Settings');
        $form = $services->get('FormElementManager')->get(ConfigForm::class);
        $form->init();

        $config = require __DIR__ . '/config/module.config.php';
        $config = $config['pdftoc']['config'];
        $data = [];
        foreach ($config as $name => $value) {
            $data[$name] = $settings->get($name, $value);
        }
        $form->setData($data);

        $html = '<p>'
            . $renderer->translate('Depending on your use case, you might want to attach the TOC directly to the media, or to the parent item.') // @translate
            . '</p>';
        $html .= $renderer->formCollection($form);
        return $html;
    }

    public function handleConfigForm(AbstractController $controller)
    {
        $services = $this->getServiceLocator();
        $settings = $services->get('Omeka\Settings');
        $form = $services->get('FormElementManager')->get(ConfigForm::class);
        $params = $controller->getRequest()->getPost();

        $form->init();
        $form->setData($params);
        $form->isValid();
        $params = $form->getData();

        $settings->set("place_store_toc", $params["place_store_toc"]);
    }


    /**
     * Attach listeners to events.
     *
     * @param SharedEventManagerInterface $sharedEventManager
     */
    public function attachListeners(SharedEventManagerInterface $sharedEventManager)
    {
        $sharedEventManager->attach(
            'Omeka\Api\Adapter\ItemAdapter',
            'api.create.post',
            [$this, 'extractToc']
        );

        $sharedEventManager->attach(
            'Omeka\Api\Adapter\ItemAdapter',
            'api.update.post',
            [$this, 'extractToc']
        );
    }

    private function iiifUrl()
    {
        $url = $this->getServiceLocator()->get('ViewHelperManager')->get('url');
        return $url('top', [], ['force_canonical' => true]) . "iiif";
    }

    public function extractToc(Event $event)
    {
        $logger = $this->getServiceLocator()->get("Omeka\Logger");
        $response = $event->getParams()['response'];
        $item = $response->getContent();

        foreach ($item->getMedia() as $media) {
            $fileExt = $media->getExtension();
            if (in_array($fileExt, array('pdf', 'PDF'))) {
                $logger->info("Lancement job pour ".$media->getStorageId());
                $filePath = OMEKA_PATH . '/files/original/' . $media->getStorageId() . '.' . $fileExt;

                $this->serviceLocator->get('Omeka\Job\Dispatcher')->dispatch('PdfToc\Job\ExtractToc',
                    [
                        'itemId' => $media->getItem()->getId(),
                        'mediaId' => $media->getId(),
                        'filePath' => $filePath,
                        'iiifUrl' => $this->iiifUrl(),
                    ]);
            }
        }
    }
}

