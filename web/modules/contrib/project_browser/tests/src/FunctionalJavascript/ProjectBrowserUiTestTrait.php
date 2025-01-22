<?php

declare(strict_types=1);

namespace Drupal\Tests\project_browser\FunctionalJavascript;

use Behat\Mink\Element\NodeElement;

/**
 * Trait used by UI tests for testing actions like clicking and dragging.
 */
trait ProjectBrowserUiTestTrait {

  /**
   * Asserts that a table row element was dragged to another spot in the table.
   *
   * @param \Behat\Mink\Element\NodeElement $element
   *   The table row element.
   * @param int|float $timeout
   *   (int) How long to wait before timing out. Defaults to 10 seconds.
   */
  protected function assertTableRowWasDragged(NodeElement $element, int|float $timeout = 10): void {
    $indicator = $element->waitFor($timeout, function (NodeElement $element): ?NodeElement {
      return $element->find('css', '.tabledrag-changed');
    });
    $this->assertInstanceOf(NodeElement::class, $indicator);
  }

  /**
   * Asserts that a given list of project titles are visible on the page.
   *
   * @param array $project_titles
   *   An array of expected titles.
   * @param bool $reload
   *   When TRUE, reload the page if the assertion fails and try again.
   *   This should typically be kept to the default value of FALSE. It only
   *   needs to be set to TRUE for calls that intermittently fail on GitLabCI.
   */
  protected function assertProjectsVisible(array $project_titles, bool $reload = FALSE): void {
    $count = count($project_titles);

    // Create a JavaScript string that checks the titles of the visible
    // projects. This is done with JavaScript to avoid issues with PHP
    // referencing an element that was rerendered and thus unavailable.
    $script = "document.querySelectorAll('#project-browser .pb-project h3 button').length === $count";
    foreach ($project_titles as $key => $value) {
      $script .= " && document.querySelectorAll('#project-browser .pb-project h3 button')[$key].textContent === '$value'";
    }

    // It can take a while for all items to render. Wait for the condition to be
    // true before asserting it.
    $this->getSession()->wait(10000, $script);

    if ($reload) {
      try {
        $this->assertTrue($this->getSession()->evaluateScript($script), 'Ran:' . $script . 'Svelte did not initialize. Markup: ' . $this->getSession()->evaluateScript('document.querySelector("#project-browser").innerHTML'));
      }
      catch (\Exception $e) {
        $this->getSession()->reload();
        $this->getSession()->wait(10000, $script);
      }
    }

    $this->assertTrue($this->getSession()->evaluateScript($script), 'Ran:' . $script . 'Svelte did not initialize. Markup: ' . $this->getSession()->evaluateScript('document.querySelector("#project-browser").innerHTML'));
  }

  /**
   * Searches for a term in the search field.
   *
   * @param string $value
   *   The value to search for.
   * @param bool $bypass_wait
   *   When TRUE, do not wait for a rerender after entering a search string.
   */
  protected function inputSearchField(string $value, bool $bypass_wait = FALSE) {
    $search_field = $this->assertSession()->waitForElementVisible('css', '#pb-text');
    if ($bypass_wait) {
      $search_field->setValue($value);
    }
    else {
      $this->preFilterWait();
      $search_field->setValue($value);
      $this->postFilterWait();
    }
  }

  /**
   * Click an element with waits for Svelte to refresh.
   *
   * @param string $locator
   *   Locator to be used by pressButton().
   * @param string $wait_for_text
   *   When non-empty, wait for this text to be present after click.
   * @param bool $bypass_wait
   *   When TRUE, do not wait for a rerender after entering a search string.
   */
  protected function pressWithWait(string $locator, string $wait_for_text = '', bool $bypass_wait = FALSE) {
    if ($bypass_wait) {
      $this->getSession()->getPage()->pressButton($locator);
    }
    else {
      $this->preFilterWait();
      $this->getSession()->getPage()->pressButton($locator);
      $this->postFilterWait();
    }

    if (!empty($wait_for_text)) {
      $this->waitForPageToContainText($wait_for_text);
    }
  }

  /**
   * Click an element with waits for Svelte to refresh.
   *
   * @param string|NodeElement $element
   *   Container element to look within.
   * @param string $wait_for_text
   *   When non-empty, wait for this text to be present after click.
   * @param bool $bypass_wait
   *   When TRUE, do not wait for a rerender after entering a search string.
   */
  protected function clickWithWait(string|NodeElement $element, string $wait_for_text = '', bool $bypass_wait = FALSE) {
    if (is_string($element)) {
      $element = $this->assertSession()->elementExists('css', $element);
    }
    $do_click = function ($element): void {
      // In W3C mode, you cannot click an <option> element. We have to set the
      // value of the containing <select> correctly.
      if (strtolower($element->getTagName()) === 'option') {
        $element->getParent()->setValue($element->getValue());
      }
      else {
        $element->click();
      }
    };

    if ($bypass_wait) {
      $do_click($element);
    }
    else {
      $this->preFilterWait();
      $do_click($element);
      $this->postFilterWait();
    }

    if (!empty($wait_for_text)) {
      $this->assertTrue($this->assertSession()->waitForText($wait_for_text));
    }
  }

  /**
   * Opens the advanced filter element.
   */
  protected function openAdvancedFilter() {
    $filter_icon_selector = $this->getSession()->getPage()->find('css', '.search__filter__toggle');
    $filter_icon_selector->click();
    $this->assertSession()->waitForElementVisible('css', '.search__filter__toggle[aria-expanded="true"]');
  }

  /**
   * Changes the sort by field.
   *
   * @param string $value
   *   The value to sort by.
   * @param bool $bypass_wait
   *   When TRUE, do not wait for a rerender after entering a search string.
   */
  protected function sortBy(string $value, bool $bypass_wait = FALSE) {
    if ($bypass_wait) {
      $this->getSession()->getPage()->selectFieldOption('pb-sort', $value);
    }
    else {
      $this->preFilterWait();
      $this->getSession()->getPage()->selectFieldOption('pb-sort', $value);
      $this->postFilterWait();
    }
  }

  /**
   * Add an attribute to a project card that will vanish after filtering.
   */
  protected function preFilterWait() {
    $this->getSession()->executeScript("document.querySelectorAll('.pb-project').forEach((project) => project.setAttribute('data-pre-filter', 'true'))");
  }

  /**
   * Confirm the attribute added in preFilterWait() is no longer present.
   */
  protected function postFilterWait() {
    $this->assertSession()->assertNoElementAfterWait('css', '[data-pre-filter]');
  }

  /**
   * Confirms Svelte initialized and will re-try once if not.
   *
   * In ~1% of GitLabCI tests, Svelte will not initialize. Since this difficulty
   * initializing is specific to GitLabCI and a refresh consistently fixes it,
   * we do an initial check and refresh when it fails.
   *
   * @param string $check_type
   *   The type of check to make (css or text)
   * @param string $check_value
   *   The value to check for.
   * @param int $timeout
   *   Timeout in milliseconds, defaults to 10000.
   */
  protected function svelteInitHelper(string $check_type, string $check_value, int $timeout = 10000) {
    if ($check_type === 'css') {
      if (!$this->assertSession()->waitForElement('css', $check_value, $timeout)) {
        $this->getSession()->reload();
        $this->assertNotNull($this->assertSession()->waitForElement('css', $check_value, $timeout), 'Svelte did not initialize. Markup: ' . $this->getSession()->evaluateScript('document.querySelector("#project-browser").innerHTML'));
      }
    }
    if ($check_type === 'text') {
      if (!$this->assertSession()->waitForText($check_value, $timeout)) {
        $this->getSession()->reload();
        $this->assertTrue($this->assertSession()->waitForText($check_value, $timeout), 'Svelte did not initialize. Markup: ' . $this->getSession()->evaluateScript('document.querySelector("#project-browser").innerHTML'));
      }
    }
  }

  /**
   * Retrieves element text with JavaScript.
   *
   * This is an alternative for accessing element text with `getText()` in PHP.
   * Use this for elements that might become "stale element references" due to
   * re-rendering.
   *
   * @param string $selector
   *   CSS selector of the element.
   *
   * @return string
   *   The trimmed text content of the element.
   */
  protected function getElementText($selector) {
    return trim($this->getSession()->evaluateScript("document.querySelector('$selector').textContent"));
  }

  /**
   * Asserts that a given list of pager items are present on the page.
   *
   * @param array $pager_items
   *   An array of expected pager item labels.
   */
  protected function assertPagerItems(array $pager_items): void {
    $page = $this->getSession()->getPage();
    $assert_session = $this->assertSession();

    $assert_session->waitForElementVisible('css', '#project-browser .pb-project');
    $items = array_map(function ($element) {
      return $element->getText();
    }, $page->findAll('css', '#project-browser .pager__item'));

    // There are two pagers, one on top and one at the bottom.
    $items = array_unique($items);
    $this->assertSame($pager_items, $items);
  }

  /**
   * Helper to wait for text on page.
   *
   * @param string $text
   *   The expected text.
   */
  protected function waitForPageToContainText(string $text): void {
    $this->assertTrue($this->assertSession()->waitForText($text), "Expected '$text' to appear on the page but it didn't.");
  }

  /**
   * Helper to wait for a field to appear on the page.
   *
   * @param string $locator
   *   The locator to use to find the field.
   * @param \Behat\Mink\Element\NodeElement|null $container
   *   The container to look within.
   */
  protected function waitForField(string $locator, ?NodeElement $container = NULL): NodeElement {
    $container ??= $this->getSession()->getPage();
    $this->assertTrue(
      $container->waitFor(10, fn ($container) => $container->findField($locator)?->isVisible()),
    );
    return $container->findField($locator);
  }

  /**
   * Helper to wait for text in a specific element.
   *
   * @param \Behat\Mink\Element\NodeElement $element
   *   The element to look within.
   * @param string $text
   *   The text to look for.
   */
  protected function waitForElementToContainText(NodeElement $element, string $text): void {
    $this->assertTrue(
      $element->waitFor(10, fn ($element) => str_contains($element->getText(), $text)),
    );
  }

}
