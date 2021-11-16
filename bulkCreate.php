<?php
	use Omeka\Entity\Job;
	require dirname(dirname(__DIR__)) . '/bootstrap.php';

	$application = Omeka\Mvc\Application::init(require OMEKA_PATH . '/application/config/application.config.php');
	$serviceLocator = $application->getServiceManager();
	$entityManager = $serviceLocator->get('Omeka\EntityManager');

	// Using user #1, admin in our case, to be allowed to update the media
	$user = $entityManager->find('Omeka\Entity\User', 1);
	$serviceLocator->get('Omeka\AuthenticationService')->getStorage()->write($user);

	// Using the API to access the media files
	$api = $serviceLocator->get('Omeka\ApiManager');

	// Should be used to only get application/pdf media
	// Not working right now, why ?
	$params = array();
	$params["media_type"] = "application/pdf";
	
	// Use the id of the property Dc:Table of content
	// We limit to PDF without this property.
	$params['property'] = [
		[
		    'property' => 18,
		    'type' => 'nex',
		]
	];
		
	$resultMedia = $api->search('media', $params)->getContent();

	$jobDispatcher = $serviceLocator->get('Omeka\Job\Dispatcher');
	foreach ($resultMedia as $media) {
		if ($media->mediaType() == "application/pdf") {
			$toc = $media->value("dcterms:tableOfContents");
			if ($toc) {
				print "#".$media->id()." already has toc, skipping\n";
			} else {
				$filePath = OMEKA_PATH . "/files/original/".$media->filename();
				print "#".$media->id()." sent to ExtractToc job\n";
				$jobDispatcher->dispatch('PdfToc\Job\ExtractToc',
					[
						'itemId' => $media->item()->id(),
			      'mediaId' => $media->id(),
			      'filePath' => $filePath,
			      'iiifUrl' => 'http://127.0.0.1/omeka-s/iiif',
					]
				);
			}
		}
	}
