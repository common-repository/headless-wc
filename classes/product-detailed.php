<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class HWC_Attributes {
    public array $data = [];
}

class HWC_Variation {
    public HWC_Attributes $attributes;
    public HWC_Product $product;
}

class HWC_Product_Detailed extends HWC_Product {
    public string $weightUnit;
    public string $dimensionUnit;
    public ?float $width = null;
    public ?float $length = null;
    public ?float $height = null;
    public ?float $weight = null;
    public array $galleryImages = [];
    public array $upsellIds = [];
    public array $crossSellIds = [];
    public ?array $content = array(
        'rendered' => '',
        'plain' => '',
    );
    /**
     * Only if $type is "variable" $variations_ prefixed params will be present
     */
    public ?array $variations;

    public function __construct( $wc_product ) {
        parent::__construct( $wc_product );
        if ( is_string( $wc_product->get_description ) ) {
            $this->content = [
                'rendered' => wp_kses_post( $wc_product->get_description() ),
                'plain' => wp_strip_all_tags( $wc_product->get_description() ),
            ];
        } else {
            $this->content = null;
        }
        $this->weightUnit = get_option( 'woocommerce_weight_unit' );
        $this->dimensionUnit = get_option( 'woocommerce_dimension_unit' );
        $this->width = nvl( $wc_product->get_width() );
        $this->length = nvl( $wc_product->get_length() );
        $this->height = nvl( $wc_product->get_height() );
        $this->weight = nvl( $wc_product->get_weight() );
        $this->galleryImages = headlesswc_get_gallery_images( $wc_product );
        $this->upsellIds = $wc_product->get_upsell_ids();
        $this->crossSellIds = $wc_product->get_cross_sell_ids();
        //$this->meta_data = headlesswc_get_meta_data( $wc_product );
        //'allData' => $wc_product->get_data(),

        ////////////////////////////////////////////////////////////////////////////////////
		if ( $wc_product->get_type() === 'variable' ) {
			$this->variations = $this->get_product_variations( $wc_product );
		}
        ////////////////////////////////////////////////////////////////////////////////////
    }

    protected function get_product_variations( $wc_product ) {
        $variation_ids = $wc_product->get_children();
        foreach ( $variation_ids as $variation_id ) {
            $variation = wc_get_product( $variation_id );
            $product = new HWC_Product( $variation );
            $variations[] = [
                'attributeValues' => $variation->get_attributes(),
                'variation' => $product->get_data(),
            ];
        }
        return $variations;
    }

    public function get_data(): array {
        $data = array_merge( parent::get_data(), get_object_vars( $this ) );
        if ( $data['type'] !== 'variable' ) {
			unset( $data['variationsMinPrice'] );
            unset( $data['variationsMaxPrice'] );
            unset( $data['variations'] );
		}
        return $data;
    }
}
