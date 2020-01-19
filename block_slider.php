<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

/**
 * Simple slider block for Moodle
 *
 * @package   block_slider
 * @copyright 2015 Kamil Łuczak    www.limsko.pl     kamil@limsko.pl
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */


define('BLOCK_SLIDER_MAX_SLIDES', 100);
define('BLOCK_SLIDER_DEFAULT_SLIDECOUNT', 1);
defined('MOODLE_INTERNAL') || die();


class block_slider extends block_base
{
    public function init() {
        $this->title = get_string('pluginname', 'block_slider');
    }

    public function get_content() {
        global $USER, $CFG;
        require_once($CFG->libdir . '/filelib.php');

        $userroles = array();
        if (isset($USER->profile['CampusRoles'])) {
            $userroles = explode(',', $USER->profile['CampusRoles']);
        }

        $useryears = array();
        if(isset($USER->profile['Year'])) {
            $useryears = explode(',', $USER->profile['Year']);
        }

        if ($this->content !== null) {
            return $this->content;
        }

        $this->content = new stdClass;

        // Check if the block is being shown on mobile and whether that is allowed.

        $showonmobile = '';
        $showondesktop = '';
        if (isset($this->config->mobile) && $this->config->mobile == 1) {
            $showonmobile = 'show-on-mobile';
        } else{
            $showondesktop = 'show-on-desktop';
        }

        if (!empty($this->config->heading)) {
            $this->content->text = $this->config->heading;
        } else {
            $this->content->text = '';
        }

        $this->content->text .= '<div class="slider ' . $showonmobile .' '.  $showondesktop .' "><div id="slides" class="slides">';
        // Get and display images.
        $fs = get_file_storage();
        $files = $fs->get_area_files($this->context->id, 'block_slider', 'content');
        $countskipped = 0;
        foreach ($files as $file) {
            $id = $file->get_contenthash();

            if( !$this->checkallowed($this->config->roles[$id], $userroles) ||
                !$this->checkyear($this->config->years[$id], $useryears)) {
                 $countskipped = $countskipped + 1;
                 continue;
            }

            $filename = $file->get_filename();
            if ($filename <> '.') {
                $src = moodle_url::make_pluginfile_url($file->get_contextid(), $file->get_component(), $file->get_filearea(), $file->get_itemid()     , $file->get_filepath(), $filename );
                if( isset($this->config->url[$id])) {
                    $url = $this->config->url[$id];
                    $this->content->text .= '<a href="' . $url . '">';
                }
                $this->content->text .= '<img src="' . $src . '" alt="' . $filename . '" />';
                if(!empty($url)) {
                    $this->content->text .= '</a>';
                }
            }
        }
        //Navigation Left/Right
        if (!empty($this->config->navigation)) {
            $this->content->text .= '<a href="#" class="slidesjs-previous slidesjs-navigation"><i class="icon fa fa-chevron-left icon-large" aria-hidden="true" aria-label="Prev"></i></a>';
            $this->content->text .= '<a href="#" class="slidesjs-next slidesjs-navigation"><i class="icon fa fa-chevron-right icon-large" aria-hidden="true" aria-label="Next"></i></a>';
        }

        $this->content->text .= '</div></div>';

        if (!empty($this->config->width) and is_numeric($this->config->width)) {
            $width = $this->config->width;
        } else {
            $width = 940;
        }

        if (!empty($this->config->height) and is_numeric($this->config->height)) {
            $height = $this->config->height;
        } else {
            $height = 528;
        }

        if (!empty($this->config->interval) and is_numeric($this->config->interval)) {
            $interval = $this->config->interval;
        } else {
            $interval = 5000;
        }

        if (!empty($this->config->effect)) {
            $effect = $this->config->effect;
        } else {
            $effect = 'fade';
        }

        if (!empty($this->config->pagination)) {
            $pag = 'true';
        } else {
            $pag = 'false';
        }

        if (!empty($this->config->autoplay)) {
            $autoplay = 'true';
        } else {
            $autoplay = 'false';
        }

        $nav = false;
        $instance = 'inst' . $this->instance->id;
        $this->save_instance_width_height($instance, $width, $height);

        $this->page->requires->js_call_amd('block_slider/slides', 'init', array($width, $height, $effect, $interval, $autoplay, $pag, $nav, $instance));
        if (count($files) < 1) {
            $this->content->heading = get_string('noimages', 'block_slider');
        }

        // Case: All mandatory fields are invalid values.
        if($countskipped == count($files) && !is_siteadmin()){
            return $this->content->text ='';
        }

        return $this->content;
    }

    function instance_delete() {
        global $DB;
        $fs = get_file_storage();
        $fs->delete_area_files($this->context->id, 'block_slider');
        return true;
    }

    function has_config()
    {
        return true;
    }

    public function instance_allow_multiple()
    {
        return true;
    }

    public function applicable_formats()
    {
        return array(
            'site' => true,
            'course-view' => true,
            'my' => true
        );
    }

    public function hide_header()
    {
        global $PAGE;
        if ($PAGE->user_is_editing()) {
            return false;
        } else {
            return true;
        }
    }
    // Checks if the role of the user is allowed to see images.
    public function checkallowed($imgroles, $userroles) {
        $imgrolesarr = array_map('trim', explode(',', $imgroles));
        $rolesallowed = array_intersect($userroles, $imgrolesarr);
        $userrolesstr = implode(',', $userroles);

        if ($imgroles == "*" || $rolesallowed || is_siteadmin()) {
            return true;
        }
        // Do regex checks.
        foreach ($imgrolesarr as $reg) {
            $regex = "/${reg}/i";
            if ($reg && (preg_match($regex, $userrolesstr) === 1)) {
                return true;
            }
        }
        return false;
    }

     public function checkyear($imgyears, $useryears) {
        $imgyearsarr = array_map('trim', explode(',', $imgyears));
        $yearssallowed = array_intersect($useryears, $imgyearsarr);
        $useryearsstr = implode(',', $useryears);
        if ($imgyears == "*" || $yearssallowed || is_siteadmin()) {
            return true;
        }

        // Do regex checks.
        foreach ($imgyearsarr as $reg) {
            $regex = "/${reg}/i";
            if ($reg && (preg_match($regex, $useryearsstr) === 1)) {
                return true;
            }
        }
        return false;
    }
    // Save the width and height of the instance as cookies.
    // For some reason the JQuery does loses the reference too this values when there is more than one block  on the same page.
    private function save_instance_width_height($instance,$width, $height) {

        if (!isset($_COOKIE[$instance . 'w'])) {
            setcookie($instance . 'w', $width, time()+ 30 * 24 * 60 * 60);
        }

        if (!isset($_COOKIE[$instance . 'h'])) {
            setcookie($instance . 'h', $height, time() + 30 * 24 * 60 * 60);
        }
    }

}