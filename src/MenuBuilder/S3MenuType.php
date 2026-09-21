<?php

declare(strict_types=1);

namespace MajesticDev\CommandNetS3\MenuBuilder;

use Forumify\Core\Entity\MenuItem;
use Forumify\Core\MenuBuilder\MenuType\AbstractMenuType;
use MajesticDev\CommandNetS3\Form\S3PagePayloadType;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Contracts\Service\Attribute\Required;
use Twig\Environment;

/**
 * An "S3" item for the site's Menu Builder. The admin ticks which S3 pages it covers (the
 * dashboard, the SOP library). A single page renders as a plain link named after the item;
 * several render as a dropdown, the same shape Command Net's own menu type uses. Pages the
 * viewer has no permission for are left out, so nobody is sent to a 403.
 *
 * Who sees the item at all is Forumify's own per-item permission in the Menu Builder.
 */
class S3MenuType extends AbstractMenuType
{
    private Environment $twig;

    public function __construct(
        private readonly UrlGeneratorInterface $urlGenerator,
        private readonly Security $viewer,
    ) {
    }

    #[Required]
    public function setTwig(Environment $twig): void
    {
        $this->twig = $twig;
    }

    public function getType(): string
    {
        return 's3';
    }

    public function getPayloadFormType(): ?string
    {
        return S3PagePayloadType::class;
    }

    protected function render(MenuItem $item): string
    {
        $selected = $item->getPayloadValue('pages') ?? [];

        $links = [];
        foreach (S3PagePayloadType::PAGES as $label => $route) {
            if (in_array($route, $selected, true) && $this->viewer->isGranted(S3PagePayloadType::PERMISSIONS[$route])) {
                $links[$label] = $this->urlGenerator->generate($route);
            }
        }

        if ($links === []) {
            return '';
        }

        if (count($links) === 1) {
            return $this->twig->render('@Forumify/frontend/menu/url.html.twig', [
                'url' => reset($links),
                'label' => $item->getName(),
                'external' => false,
            ]);
        }

        $inner = '';
        foreach ($links as $label => $url) {
            $inner .= $this->twig->render('@Forumify/frontend/menu/url.html.twig', [
                'url' => $url,
                'label' => $label,
                'external' => false,
            ]);
        }

        return $this->twig->render('@Forumify/frontend/menu/collection.html.twig', [
            'name' => $item->getName(),
            'placement' => $item->getParent() === null ? 'bottom-start' : 'right-start',
            'inner' => $inner,
        ]);
    }
}
