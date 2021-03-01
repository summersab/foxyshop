<?php
//Exit if not called in proper context
if (!defined('ABSPATH')) exit();

function foxyshop_xml_product_feed() {
    global $product_feed_field_names, $product, $foxyshop_settings;
    $xml = '<?xml version="1.0"?>
		<feed xmlns="http://www.w3.org/2005/Atom" xmlns:g="http://base.google.com/ns/1.0">';

	$productPerms = array();
    $products = get_posts(array('post_type' => 'foxyshop_product', 'post_status' => "publish"));
    foreach($products as $singleproduct) {
$product = foxyshop_setup_product($singleproduct);
if ($product['code'] == 'R') {
//file_put_contents(ABSPATH . 'killroy', print_r($product,1) . "\n");
file_put_contents(ABSPATH . 'killroy', print_r(get_post_meta($product['id']),1) . "\n");
}
        //Initialize $product
		$product = foxyshop_variations_as_arrays($singleproduct);
		foxyshop_product_permutations($product, $productPerms);
	}

	foreach ($productPerms as $product) {
        //Setup a few things
        $condition = get_post_meta($product['id'],'_condition',1);
        if (!$condition) $condition = "new";

        $availability = get_post_meta($product['id'],'_availability',1);
        if (!$availability) $availability = "in stock";

        $product_type_write = "";
        $categories = wp_get_post_terms($product['id'], 'foxyshop_categories');
        foreach ($categories as $cat) {
            if ($product_type_write == "") {
                $breadcrumbarray = array_reverse(get_ancestors($cat->term_id, 'foxyshop_categories'));
                foreach ($breadcrumbarray as $crumb) {
                    $term = get_term_by('id', $crumb, 'foxyshop_categories');
                    $product_type_write .= $term->name . ' > ';
                }
                $product_type_write .= $cat->name;
            }
        }

        if ($product['originalprice'] != $product['price']) {
			date_default_timezone_set(get_option('timezone_string'));
            $salestartdate = get_post_meta($product['id'],'_salestartdate',1);
            $saleenddate = get_post_meta($product['id'],'_saleenddate',1);
            if ($salestartdate == '999999999999999999') $salestartdate = 0;
            if ($saleenddate == '999999999999999999') $saleenddate = 0;
            $salestartdate = ($salestartdate == 0 ? Date('c', strtotime("-1 day")) : Date('c', $salestartdate));
            $saleenddate = ($saleenddate == 0 ? Date('c', strtotime("+1 year")) : Date('c', $saleenddate));
            $sale_price_effective_date = $salestartdate."/".$saleenddate;
        }

		$gtin = $product['code'];
		$mpn = $product['code'];
		$condition = "new";
		$brand = get_bloginfo('name');

//Killroy
//look at this again:
        foreach($product_feed_field_names as $field) {
            $val = get_post_meta($product['id'],'_'.$field,1);
			if ($field == 'condition' && !$val) $val = $condition;
	        if ($field == 'gtin' && !$val) $val = $gtin;
    	    if ($field == 'mpn' && !$val) $val = $mpn;
        	if ($field == 'brand' && !$val) $val = $brand;
//            if ($val) $xml .= '<scp:'.$field.'>' . esc_attr($val) . '</scp:'.$field.'>'."\n";
        }

		$xml .= '<entry>'."\n";
		//<!-- The following attributes are always required -->
		$xml .= 	'<g:id>' . esc_attr($product['id']) . '</g:id>'."\n";
		$xml .= 	'<g:title>' . esc_attr(trim($product['name'])) . '</g:title>'."\n";
		$xml .= 	'<g:description><![CDATA[' . esc_attr($product['description']) . ']]></g:description>'."\n";
		$xml .= 	'<g:link>' . esc_attr($product['url']) . '</g:link>'."\n";
		$xml .= 	'<g:image_link>' . foxyshop_get_main_image('large') . '</g:image_link>'."\n";
		$xml .= 	'<g:availability>' . esc_attr($availability) . '</g:availability>'."\n";
		$xml .= 	'<g:price>' . $product['originalprice'] . ' ' . apply_filters('foxyshop_google_product_currency', 'USD') . '</g:price>'."\n";
		$xml .= 	'<g:shipping>'."\n";
		$xml .= 		'<g:country>' . apply_filters('foxyshop_google_product_target_country', 'US') . '</g:country>'."\n";
		$xml .= 		'<g:service>Standard</g:service>'."\n";
		$xml .= 		'<g:price>10.00 ' . apply_filters('foxyshop_google_product_currency', 'USD') . '</g:price>'."\n";
		$xml .= 	'</g:shipping>'."\n";

        foreach($product_feed_field_names as $field) {
            $val = get_post_meta($product['id'],'_'.$field,1);
            if ($field == 'condition' && !$val) $val = $condition;
            if ($field == 'gtin' && !$val) $val = $gtin;
            if ($field == 'mpn' && !$val) $val = $mpn;
            if ($field == 'brand' && !$val) $val = $brand;
            if ($val) $xml .= '<g:'.$field.'>' . esc_attr($val) . '</g:'.$field.'>'."\n";
        }

		//<!-- The following attributes are not required for this item, but supplying them is recommended -->
        //Additional Images
/*        $number_of_additional_images = 0;
        foreach($product['images'] as $product_image) {
            $number_of_additional_images++;
            if ($product_image['featured'] == 0 && $number_of_additional_images <= 10) {
                $xml .= '<g:additional_image_link>' . $product_image['large'] . '</g:additional_image_link>'."\n";
            }
        }
*/
		if ($product['originalprice'] != $product['price']) {
			$xml .= '<g:sale_price>' . $product['price'] . ' ' . apply_filters('foxyshop_google_product_currency', 'USD') . '</g:sale_price>'."\n";
			$xml .= '<g:sale_price_effective_date>' . $sale_price_effective_date . '</g:sale_price_effective_date>'."\n";
		}

//        if ($product_type_write) $xml .= '<scp:product_type>' . esc_attr($product_type_write) . '</scp:product_type>'."\n";


//		$xml .= 	"<g:google_product_category>Electronics > Video > Televisions > Flat Panel Televisions</g:google_product_category>\n";
//		$xml .= 	"<g:product_type>Consumer Electronics &gt; TVs &gt; Flat Panel TVs</g:product_type>\n";

        $xml .= '</entry>'."\n";
    }

	$xml .= '</feed>';
    return $xml;
}

function foxyshop_product_permutations($product, &$productPerms) {
	$mods = array('c', 'w', 'p', 'y', 'ikey', 'fr');
	
	$varKey = NULL;
	$variation = array();
	foreach ($product['variations'] as $varKey => $variation) {
    	break;
	}

	if (isset($variation['required']) && !$variation['required']) {
		$prodRecur = $product;
		unset($prodRecur['variations'][$varKey]);
		foxyshop_product_permutations($prodRecur, $productPerms);
	}
	foreach ($variation as $varOpKey => $varOp) {
        if (is_array($variation[$varOpKey])) {
            $prodRecur = $product;
			foreach ($varOp as $key => $op) {
				if (isset($op['param'])) {
					if (in_array($op['param'], $mods)) {
						switch ($op['param']) {
							case 'c':
								switch ($op['oper']) {
									case '=':
										$prodRecur['code'] = $op['val'];
										break;
									case '+':
										$prodRecur['code'] .= $op['val'];
										break;
									case '-':
										$prodRecur['code'] = rtrim($prodRecur['code'], $op['val']);
										break;
								}
								break;
                            case 'p':
                                switch ($op['oper']) {
                                    case '=':
                                        $prodRecur['price'] = $op['val'];
										$prodRecur['originalprice'] = $op['val'];
                                        break;
                                    case '+':
										$prodRecur['price'] += $op['val'];
                                        $prodRecur['originalprice'] += $op['val'];
										break;
                                    case '-':
                                        $prodRecur['price'] -= $op['val'];
                                        $prodRecur['originalprice'] -= $op['val'];
                                        break;
                                }
                                break;
                            case 'w':
                                switch ($op['oper']) {
                                    case '=':
                                        $prodRecur['weight'] = $op['val'];
                                        break;
                                    case '+':
                                        $prodRecur['weight'] += $op['val'];
                                        break;
                                    case '-':
                                        $prodRecur['weight'] -= $op['val'];
                                        break;
                                }
                                break;
                            case 'y':
                                switch ($op['oper']) {
                                    case '=':
                                        $prodRecur['category'] = $op['val'];
                                        break;
                                    case '+':
                                        $prodRecur['category'] .= $op['val'];
                                        break;
                                    case '-':
                                        $prodRecur['category'] = rtrim($prodRecur['category'], $op['val']);
                                        break;
                                }
                                break;
                            case 'ikey':
                                switch ($op['oper']) {
                                    case '=':
                                        $prodRecur['ikey'] = $op['val'];
                                        break;
                                    //case '+':
                                    //    $prodRecur['ikey'] .= $op['val'];
                                    //    break;
                                    //case '-':
                                    //    break;
                                }
                                break;
                            case 'fr':
                                switch ($op['oper']) {
                                    case '=':
                                        $prodRecur['code'] = $op['val'];
                                        break;
                                    //case '+':
                                    //    $prodRecur['code'] .= $op['val'];
                                    //    break;
                                    //case '-':
                                    //    break;
                                }
                                break;
						}
					}
				}
			}
			unset($prodRecur['variations'][$varKey]);
			if (sizeof($prodRecur['variations']) == 0) {
				if (!in_array($prodRecur, $productPerms)) {
					array_push($productPerms, $prodRecur);
				}
			}
			else {
				foxyshop_product_permutations($prodRecur, $productPerms);
			}
		}
	}
}

function foxyshop_variations_as_arrays($singleproduct) {
	$product = foxyshop_setup_product($singleproduct);
	foreach ($product['variations'] as $varKey => &$variation) {
		$required = false;
		if ($variation['required'] == "on") {
			$required = true;
		};
		$variation = explode("\n", $variation['value']);
		foreach ($variation as &$varOp) {
			$varOp = substr($varOp, strpos($varOp, '{') + 1);
			$varOp = substr($varOp, 0, strpos($varOp, '}'));
			$varOp = explode('|',$varOp);
			foreach ($varOp as $key => $op) {
				$params = array(
					'param'	=> "",
					'oper'	=> "",
					'val'	=> "",
					);
				if (strpos($op, ':') !== false) {
					$op = explode(':', $op);
					$params['oper'] = '=';
				}
				else if (strpos($op, '+') !== false) {
					$op = explode('+', $op);
					$params['oper'] = '+';
				}
				else if (strpos($op, '-') !== false) {
					$op = explode('-', $op);
					$params['oper'] = '-';
				}
				$params['val'] = $op[1];
                $params['param'] = $op[0];
                $varOp[$key] = $params;
			}
		}
		$variation['required'] = $required;
	}
	return $product;
}
