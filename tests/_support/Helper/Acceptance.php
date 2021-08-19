<?php

namespace Helper;

// here you can define custom actions
// all public methods declared in helper class will be available in $I

use Codeception\Test\Descriptor;

class Acceptance extends \Codeception\Module {

	private $pages = [];
	private $previous_pages = [];
	private $env = '';
	private $output_file = '';
	private $current_page = '';
	private $screenshot_file = '';
	private $path_to_directory_prefix = '/visual-autotesting/tests';
	const ONLY_VISUAL_CHANGES_MESSAGE = 'Страница содержит только визуальные отличия от эталона';
	const ERROR_OCCURED_MESSAGE = 'Произошла ошибка при снятии скриншота';

	/**
	 * @param string $name
	 * @return array
	 */
	public function getPage(string $name): array {
		return $this->pages[$name];
	}

	/**
	 * @param \Codeception\Example $example
	 */
	public function addPageToOutput(\Codeception\Example $example): void {
		$this->current_page = $example['name'];
		$this->pages[$this->current_page]['url'] = $example['url'];
		foreach ($this->config['stands'] as $index => $stand) {
			$this->pages[$this->current_page]['stands'][$index] = $stand . $example['url'];
		}
	}

	/**
	 * @param string $page
	 * @param string $message
	 */
	public function preComment(string $page, string $message)  {
		switch (true) {
			case !$this->previous_pages:
			case !array_key_exists($page, $this->previous_pages):
			case !array_key_exists($this->env, $this->previous_pages[$page]):
			case !array_key_exists('status', $this->previous_pages[$page][$this->env]):
			case $this->previous_pages[$page][$this->env]['status'] == 'error':
			case $this->previous_pages[$page][$this->env]['status'] == 'critical':
				$this->pages[$page][$this->env]['message'] = $message;
				$this->pages[$page][$this->env]['status'] = 'error';
				break;
			default:
				$this->pages[$page][$this->env]['message'] = $message . '. Предыдущий тест был без этой ошибки';
				$this->pages[$page][$this->env]['status'] = 'critical';
				break;
		}
	}

	/**
	 * @param string $page
	 */
	public function preCommentVisualDeviation(string $page): void {
		$this->pages[$page][$this->env]['message'] = self::ONLY_VISUAL_CHANGES_MESSAGE;
		$this->pages[$page][$this->env]['status'] = 'warning';
	}

	/**
	 * @param string $page
	 */
	public function clearPreComments(string $page): void {
		$this->pages[$page][$this->env]['message'] = false;
		$this->pages[$page][$this->env]['status'] = 'success';
	}

	public function _before(\Codeception\TestInterface $test)
	{
		$pictureNameRaw = Descriptor::getTestSignatureUnique($test);
		$pictureNameRaw = str_replace(":", ".", $pictureNameRaw);
		$this->screenshot_file = $pictureNameRaw . ".page.png";
	}

	public function _after(\Codeception\TestInterface $test)
	{
		$status = false;
		$message = false;
		if ($this->pages && array_key_exists($this->current_page, $this->pages) && array_key_exists($this->env, $this->pages[$this->current_page])) {
			if (array_key_exists('status', $this->pages[$this->current_page][$this->env])) {
				$status = $this->pages[$this->current_page][$this->env]["status"];
			}
			if (array_key_exists('message', $this->pages[$this->current_page][$this->env])) {
				$message = $this->pages[$this->current_page][$this->env]["status"];
			}
		}
		if ($status == 'critical' || $status == 'error') {
			if (file_exists(realpath(dirname(dirname(dirname(__FILE__)))) . "/_output/debug/compare" . $this->screenshot_file)) {
				unlink(realpath(dirname(dirname(dirname(__FILE__)))) . "/_output/debug/compare" . $this->screenshot_file);
			}
			if (file_exists(realpath(dirname(dirname(dirname(__FILE__)))) . "/_output/debug/visual/" . $this->screenshot_file)) {
				unlink(realpath(dirname(dirname(dirname(__FILE__)))) . "/_output/debug/visual/" . $this->screenshot_file);
			}
			$this->pages[$this->current_page][$this->env]["example"] = false;
			$this->pages[$this->current_page][$this->env]["compare"] = false;
			if ($status == 'error') {
				$this->pages[$this->current_page][$this->env]["reference"] = false;
			}
		}
		if (file_exists(realpath(dirname(dirname(dirname(__FILE__)))) . "/_data/VisualCeption/" . $this->screenshot_file) && $status != 'error') {
			$this->pages[$this->current_page][$this->env]["reference"] = $this->path_to_directory_prefix . "/_data/VisualCeption/" . $this->screenshot_file;
		} else {
			$this->pages[$this->current_page][$this->env]["reference"] = false;
		}
		if (file_exists(realpath(dirname(dirname(dirname(__FILE__)))) . "/_output/debug/visual/" . $this->screenshot_file) && $status != 'critical') {
			$this->pages[$this->current_page][$this->env]["example"] = $this->path_to_directory_prefix . "/_output/debug/visual/" . $this->screenshot_file;
		} else {
			$this->pages[$this->current_page][$this->env]["example"] = false;
		}
		if (!$this->pages[$this->current_page][$this->env]["example"] && !$this->pages[$this->current_page][$this->env]["reference"] && $message) {
			if ($this->pages[$this->current_page][$this->env]["message"] == self::ONLY_VISUAL_CHANGES_MESSAGE) {
				$this->pages[$this->current_page][$this->env]["message"] = self::ERROR_OCCURED_MESSAGE;
			}
		}
		$this->pages[$this->current_page][$this->env]["date"] = time();
	}

	public function _failed(\Codeception\TestInterface $test, $fail)
	{
		if (file_exists(realpath(dirname(dirname(dirname(__FILE__)))) . "/_output/debug/compare" . $this->screenshot_file)) {
			$this->pages[$this->current_page][$this->env]["compare"] = $this->path_to_directory_prefix . "/_output/debug/compare" . $this->screenshot_file;
		} else {
			$this->pages[$this->current_page][$this->env]["compare"] = false;
		}
		$this->pages[$this->current_page][$this->env]['toggled'] = false;
	}

    public function _beforeSuite($settings = Array()) {
		date_default_timezone_set('Europe/Moscow');
		$this->env = $settings['current_environment'];
		$this->output_file = realpath(dirname(__FILE__)) . "/../../../json/" . $this->env . ".json";
		if (file_exists(realpath(dirname(__FILE__)) . "/../../../json/" . $this->env . ".json")) {
			$this->previous_pages = json_decode(file_get_contents(realpath(dirname(__FILE__)) . "/../../../json/" . $this->env . ".json"), null, 512, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_OBJECT_AS_ARRAY);
		}
	}

    public function _afterSuite() {
		file_put_contents($this->output_file, json_encode($this->pages, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
    }
}
