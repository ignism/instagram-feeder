<?php
/**
 * @package Instagram Feeder
 * @version 0.1
 */
/*
Plugin Name: Instagram feeder
Plugin URI:
Description:
Author: Mark Brand
Version: 0.1
Author URI: http://bymarkbrand.com
*/

defined('ABSPATH') or die('No script kiddies please!');

require 'vendor/autoload.php';

if (!class_exists('InstagramFeeder')) {
    class InstagramFeeder
    {
        protected $connection;
        protected $api;
        protected $config;

        public function __construct($token)
        {
            $this->config = array(
                'feed_limit' => 5
            );

            $this->api = array(
                'type' => 'instagram',
                'auth' => array(
                    'key' => '',
                    'secret' => '',
                    'token' => $token
                )
            );
            $this->init();
        }

        //

        public function init()
        {
            add_action('rest_api_init', function () {
                register_rest_route(
                    'instagram-feeder',
                    'media',
                    array(
                        'methods' => 'GET',
                        'callback' => array($this, 'get_media'),
                        'args' => array()
                    )
                );
            });
        
            $this->connection = new Andreyco\Instagram\Client(
                array(
                'apiKey'      => $this->api['auth']['key'],
                'apiSecret'   => $this->api['auth']['secret'],
                'apiCallback' => 'https://bymarkbrand.com',
                'scope'       => array('basic'),
                )
            );
            
            $this->connection->setAccessToken($this->api['auth']['token']);
        }

        //

        public function get_media()
        {
            $results = $this->connection->getUserMedia('self', $this->config['feed_limit']);
            
            if ($results->data) {
                return $this->sort_data($results->data);
            }
            return false;
        }

        //

        public function sort_data($data)
        {
            $sorted_data = array();

            if ($transient_data = get_transient('instagram_feed')) {
                return $transient_data;
            } else {
                foreach ($data as $item) {
                    // $caption = $item['caption']['text'];
                    $caption = $item->caption->text;
                    $img_src = $item->images->standard_resolution->url;
                    $tags = $item->tags;
                    $link = $item->link;

                    $object = array(
                    'caption' => $caption,
                    'img_src' => $img_src,
                    'tags' => $tags,
                    'link' => $link
                );

                    array_push($sorted_data, $object);

                    if (sizeof($sorted_data) == 3) {
                        break;
                    }
                }
                set_transient('instagram_feed', $sorted_data, 3600);
                return $sorted_data;
            }

            return false;
        }
    }
}

// Initialize with user token
$instagramFeeder = new InstagramFeeder("TOKEN_PLACEHOLDER");
