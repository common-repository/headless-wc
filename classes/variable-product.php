<?php
if (!defined('ABSPATH')) {
    exit;
}

class HWC_Variable_Product extends HWC_Simple_Product
{
    public function get_base_data()
    {
        $wc_product = $this->wc_product;
        return [
            ...parent::get_base_data(),
            'min_price' => $wc_product->get_variation_price('min', true),
            'max_price' => $wc_product->get_variation_price('max', true),
            'attributes' => $this->get_attributes(),
        ];
    }

    public function get_detailed_data()
    {
        $wc_product = $this->wc_product;
        return [
            ...$this->get_base_data(),
            ...parent::get_detailed_data(),
            'attributes_data' => $this->get_attributes_data(),
            'attributes_default_values' => $wc_product->get_default_attributes(),
            'variations' => $this->get_variations(),
        ];
    }



    public function get_variations()
    {
        $variation_ids = $this->wc_product->get_children();
        foreach ($variation_ids as $variation_id) {
            $variation = wc_get_product($variation_id);
            $product = new HWC_Simple_Product($variation);
            $a = $variation->get_attributes();
            $variations[] = [
                'attributes' => $variation->get_attributes(),
                'product' => $product->get_detailed_data(),
                //'allData' => $variation->get_data(),
            ];
        }
        return $variations;
    }


    public function get_attributes()
    {
        $attributes = [];
        foreach ($this->wc_product->get_attributes() as $attribute) {
            $attributes[] = $attribute->get_name();
        }
        return $attributes;
    }

    public function get_attributes_data()
    {
        $attributes_data = [];
        $product = $this->wc_product;

        foreach ($product->get_attributes() as $attribute) {
            $attribute_name = $attribute->get_name();
            $attribute_slug = $attribute->get_taxonomy(); // Assuming taxonomy slug
            $attribute_description = ''; // Placeholder for description (custom meta or predefined text)

            // Check if the attribute is a taxonomy (e.g., color, size)
            if ($attribute->is_taxonomy()) {
                $taxonomy = $attribute->get_taxonomy(); // Get taxonomy name (e.g., pa_color)
                $term_ids = $attribute->get_options(); // Get term IDs

                $terms_with_meta = [];
                foreach ($term_ids as $term_id) {
                    $term = get_term($term_id, $taxonomy);
                    if (!is_wp_error($term)) {
                        $term_name = $term->name;
                        $term_slug = $term->slug;
                        $term_description = $term->description; // Assuming term description

                        $terms_with_meta[] = [
                            'name' => $term_name,
                            'slug' => $term_slug,
                            'description' => $term_description,
                            'meta' => get_term_meta($term_id) // Get all meta fields for the term
                        ];
                    }
                }

                // Sort terms by name
                usort($terms_with_meta, function ($a, $b) {
                    return strcmp($a['name'], $b['name']);
                });

                $attributes_data[] = [
                    'name' => $attribute_name,
                    'slug' => $attribute_slug,
                    'description' => $attribute_description,
                    'values' => $terms_with_meta
                ];

            } else {
                // For non-taxonomy (custom) attributes, directly use the options
                $attribute_values = $attribute->get_options();

                // Sort values alphabetically
                sort($attribute_values);

                $attributes_data[] = [
                    'name' => $attribute_name,
                    'slug' => '', // Custom attributes typically don’t have slugs
                    'description' => '', // Custom attributes typically don’t have descriptions
                    'values' => array_map(function ($value) {
                        return ['value' => $value];
                    }, $attribute_values)
                ];
            }
        }

        return $attributes_data;
    }
}

