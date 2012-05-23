<?php

namespace Liip\Drupal\Testing\Helper;

/**
 * This class was originally written by the Migipedia team at Liip.
 * 
 * It simply wraps the Drupal functions into a class to facilitate the mocking
 * of Drupal during testing.
 *
 * Please order the functions alphabetically!
 */
class DrupalConnector {

    public function bootstrapDrupal() {

        if (defined('DRUPAL_ROOT')) {
            $_SERVER['REQUEST_METHOD'] = 'get';
            $_SERVER['REMOTE_ADDR'] = NetHelper::getServerAddress();

            require_once DRUPAL_ROOT . '/includes/bootstrap.inc';

            drupal_bootstrap(DRUPAL_BOOTSTRAP_FULL);

        } else {
            throw new \InvalidArgumentException('Constant DRUPAL_ROOT is not defined.');
        }
    }

    public function arg($index = NULL, $path = NULL) {
        return arg($index, $path);
    }

    public function base_url() {
        global $base_url;
        return $base_url;
    }

    public function cache_get($key, $table = 'cache') {
        return cache_get($key, $table);
    }

    public function cache_set($cid, $data, $table = 'cache', $expire = CACHE_PERMANENT, $headers = NULL)  {
        return cache_set($cid, $data, $table, $expire, $headers);
    }

//    public function commentquality_mark_cq_item_for_index($cqId) {
//        return commentquality_mark_cq_item_for_index($cqId);
//    }
//
//    public function community_tags_get_node_tags($nid) {
//        return community_tags_get_node_tags($nid);
//    }

    public function db_affected_rows() {
        return db_affected_rows();
    }

    public function db_error() {
        return db_error();
    }

    public function db_fetch_array($result) {
        return db_fetch_array($result);
    }

    public function db_fetch_object($result) {
        return db_fetch_object($result);
    }

    public function db_query() {
        $args = func_get_args();
        return call_user_func_array('db_query', $args);
    }

    public function db_result($resource) {
        return db_result($resource);
    }

    public function db_rewrite_sql($query, $primary_table = 'n', $primary_field = 'nid', $args = array()) {
        return db_rewrite_sql($query, $primary_table, $primary_field, $args);
    }

    public function drupal_access_denied() {
        drupal_access_denied();
    }

    public function drupal_add_css($path = NULL, $type = 'module', $media = 'all', $preprocess = TRUE) {
        return drupal_add_css($path, $type, $media, $preprocess);
    }

    public function drupal_add_js($data = NULL, $type = 'module', $scope = 'header', $defer = FALSE, $cache = TRUE, $preprocess = TRUE) {
        return drupal_add_js($data, $type, $scope, $defer, $cache, $preprocess);
    }

    public function drupal_anonymous_user() {
        return drupal_anonymous_user();
    }

    public function drupal_execute($form_id, &$form_state) {
        return drupal_execute($form_id, $form_state);
    }

    public function drupal_get_breadcrumb() {
        return drupal_get_breadcrumb();
    }

    public function drupal_get_form() {
        $args = func_get_args();
        return call_user_func_array('drupal_get_form', $args);
    }

    public function drupal_get_path($type, $name) {
        return drupal_get_path($type, $name);
    }

    public function drupal_get_path_alias($path, $path_language = '') {
        return drupal_get_path_alias($path, $path_language);
    }

    public function drupal_get_private_key() {
        return drupal_get_private_key();
    }

    public function drupal_goto($path = '', $query = NULL, $fragment = NULL, $http_response_code = 302) {
        drupal_goto($path, $query, $fragment, $http_response_code);
    }

    public function drupal_not_found() {
        return drupal_not_found();
    }

    public function drupal_set_breadcrumb($breadcrumb) {
        return drupal_set_breadcrumb($breadcrumb);
    }

    public function drupal_set_message($message = NULL, $type = 'status', $repeat = TRUE) {
        return drupal_set_message($message, $type, $repeat);
    }

    public function &drupal_static($name, $default_value = NULL, $reset = FALSE) {
        $static = &drupal_static($name, $default_value, $reset);
        return $static;
    }

    public function drupal_unpack($obj, $field = 'data') {
        return drupal_unpack($obj, $field);
    }

    public function field_file_load($fid, $reset = NULL) {
        return field_file_load($fid, $reset);
    }

    public function imagecache_create_url($preset_name, $image_path) {
        return imagecache_create_url($preset_name, $image_path);
    }

    public function ip_address() {
        return ip_address();
    }

//    public function karma_get_topusers_alltime($limit = 0, $offset = null) {
//        if ($this->module_exists('karma')) {
//            return karma_get_topusers_alltime($limit, $offset);
//        } else {
//            throw new BadFunctionCallException('Missing Drupal Module (Karma).');
//        }
//    }
//
//    public function karma_get_topusers_current($limit = 0, $offset = null) {
//        if ($this->module_exists('karma')) {
//            return karma_get_topusers_current($limit, $offset);
//        } else {
//            throw new BadFunctionCallException('Missing Drupal Module (Karma).');
//        }
//    }
//
//    public function migipedia_language_switch($language) {
//        return migipedia_language_switch($language);
//    }
//
//    public function migipedia_userinformation($uid) {
//        return migipedia_userinformation($uid);
//    }

    public function module_exists($module) {
        return module_exists($module);
    }

    public function module_invoke_all($hook) {
        return module_invoke_all($hook);
    }

    public function l($text, $path, $options = array()) {
        return l($text, $path, $options);
    }

    public function menu_execute_active_handler($path = NULL) {
        return menu_execute_active_handler($path);
    }

    public function menu_set_active_item($path) {
        return menu_set_active_item($path);
    }

//    public function migipedia_blog_api_load($lang = 'de', $is_front = false, $uid = null, $limit = 10, $termid = '') {
//        return migipedia_blog_api_load($lang, $is_front, $uid, $limit, $termid);
//    }
//
//    public function migipedia_blog_preprocess_blog_node($node) {
//        return migipedia_blog_preprocess_blog_node($node);
//    }
//
//    public function migipedia_generic_check_is_xhr() {
//        return migipedia_generic_check_is_xhr();
//    }
//
//    public function migipedia_generic_get_mid_from_nid($nid) {
//        return migipedia_generic_get_mid_from_nid($nid);
//    }
//
//    public function migipedia_generic_get_nid_from_mids(array $mids, $language) {
//        return migipedia_generic_get_nid_from_mids($mids, $language);
//    }
//
//    public function migipedia_generic_get_tnid_from_nid($nid) {
//        return migipedia_generic_get_tnid_from_nid($nid);
//    }
//
//    public function migipedia_generic_get_taxonomy_category_tid_by_term($code) {
//        return migipedia_generic_get_taxonomy_category_tid_by_term($code);
//    }
//
//    public function migipedia_generic_theme_username($uid, $link = true) {
//        return migipedia_generic_theme_username($uid, $link);
//    }
//
//    public function migipedia_teaser_get_latest_teaser() {
//        return migipedia_teaser_get_latest_teaser();
//    }

    public function node_access($op, $node, $account = NULL) {
        return node_access($op, $node, $account);
    }

    public function node_load($param = array(), $revision = NULL, $reset = NULL) {
        $node = node_load($param, $revision, $reset);
        return $node;
    }

    public function node_save(&$node) {
        return node_save($node);
    }

    public function node_view($node, $teaser = FALSE, $page = FALSE, $links = TRUE) {
        return node_view($node, $teaser, $page, $links);
    }

    public function pager_query($query, $limit = 10, $element = 0, $count_query = NULL) {
        $args = func_get_args();
        return call_user_func_array('pager_query', $args);
    }

    public function product_taxonomy_get_term_by_description($desc, $vid = 0) {
        return product_taxonomy_get_term_by_description($desc, $vid);
    }

    public function session_id() {
        return session_id();
    }

    public function t($string, $args = array(), $langcode = NULL) {
        return t($string, $args, $langcode);
    }

    public function taxonomy_get_term($tid, $reset = FALSE) {
        return taxonomy_get_term($tid, $reset);
    }

    public function theme() {
        $args = func_get_args();
        return call_user_func_array('theme', $args);
    }

    public function theme_pager($tags = array(), $limit = 10, $element = 0, $parameters = array(), $quantity = 9) {
        return theme_pager($tags, $limit, $element, $parameters, $quantity);
    }

    public function url($path = NULL, $options = array()) {
        return url($path, $options);
    }

    public function user_access($string, $account = NULL, $reset = FALSE) {
        return user_access($string, $account, $reset);
    }

    public function user_authenticate($form_values = array()) {
        return user_authenticate($form_values);
    }

    public function user_compare_roles(stdClass $user, array $roles) {
        return array_intersect($user->roles, $roles);
    }

    public function user_delete($uid) {
        user_delete($uid);
    }

    public function user_is_logged_in() {
        return user_is_logged_in();
    }

    public function user_load($user_info = array()) {
        return user_load($user_info);
    }

    public function user_password($length = 10) {
        return user_password($length);
    }

    public function user_role_grant_permissions($rid, array $permissions = array()) {
        return user_role_grant_permissions($rid, $permissions);
    }

    public function user_role_save($role) {
        return user_role_save($role);
    }

    public function user_save($account, $array = array(), $category = 'account') {
        return user_save($account, $array, $category);
    }

    public function variable_get($name, $default) {
        return variable_get($name, $default);
    }

    public function watchdog($type, $message, $variables = array(), $severity = WATCHDOG_NOTICE, $link = NULL) {
        return watchdog($type, $message, $variables, $severity, $link);
    }
}
