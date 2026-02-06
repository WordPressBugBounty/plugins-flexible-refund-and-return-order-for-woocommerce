<?php

namespace FRFreeVendor\WPDesk\Library\Marketing\Boxes;

use FRFreeVendor\WPDesk\Library\Marketing\Boxes\Abstracts\BoxInterface;
use FRFreeVendor\WPDesk\View\Renderer\Renderer;
use FRFreeVendor\WPDesk\View\Renderer\SimplePhpRenderer;
use FRFreeVendor\WPDesk\View\Resolver\ChainResolver;
use FRFreeVendor\WPDesk\View\Resolver\DirResolver;
/**
 * Renders fields boxes on the submitted data.
 */
class BoxRenderer
{
    /** @var array<string, array{type: string}> */
    private $boxes;
    /** @var Renderer */
    private $renderer;
    /** @var Helpers\BBCodes */
    private $bbcodes;
    /** @var Helpers\Markers */
    private $markers;
    /** @param array<string, array{type: string}> $boxes */
    public function __construct(array $boxes, ?Renderer $renderer = null)
    {
        $this->boxes = $boxes;
        $this->renderer = $renderer ?? new SimplePhpRenderer(new DirResolver(__DIR__ . '/Views/'));
        $this->bbcodes = new Helpers\BBCodes();
        $this->markers = new Helpers\Markers();
    }
    public function has_boxes(): bool
    {
        return !empty($this->boxes);
    }
    public function has_box(string $box_id): bool
    {
        return isset($this->boxes[$box_id]);
    }
    /**
     * Get single marketing box.
     */
    public function get_single(string $box_id): string
    {
        if ($this->has_box($box_id)) {
            $box = $this->get_box_type($this->boxes[$box_id]);
            return $box->render(['bbcodes' => $this->bbcodes, 'markers' => $this->markers]);
        }
        return '';
    }
    /**
     * Get all marketing boxes (displays all boxes in the layout).
     */
    public function get_all(): string
    {
        return $this->renderer->render('all', ['boxes' => $this->boxes, 'renderer' => $this->renderer, 'plugin' => $this, 'bbcodes' => $this->bbcodes, 'markers' => $this->markers]);
    }
    /**
     * @param array{type: string} $box
     */
    public function get_box_type(array $box): BoxInterface
    {
        switch ($box['type']) {
            case 'slider':
                return new BoxType\SliderBox($box, $this->renderer);
            case 'image':
                return new BoxType\ImageBox($box, $this->renderer);
            case 'video':
                return new BoxType\VideoBox($box, $this->renderer);
            case 'simple':
                return new BoxType\SimpleBox($box, $this->renderer);
            default:
                return new BoxType\UnknownBox($box, $this->renderer);
        }
    }
}
