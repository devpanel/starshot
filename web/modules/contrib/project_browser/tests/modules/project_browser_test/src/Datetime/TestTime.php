<?php

namespace Drupal\project_browser_test\Datetime;

use Drupal\Component\Datetime\TimeInterface;

/**
 * Test service for altering the request time.
 */
class TestTime implements TimeInterface {

  /**
   * Constructs a TestTime object.
   *
   * @param \Drupal\Component\Datetime\TimeInterface $decorated
   *   The decorated time service.
   */
  public function __construct(protected TimeInterface $decorated) {}

  /**
   * {@inheritdoc}
   */
  public function getRequestMicroTime() {
    return $this->decorated->getRequestMicroTime();
  }

  /**
   * {@inheritdoc}
   */
  public function getCurrentTime() {
    return $this->decorated->getCurrentTime();
  }

  /**
   * {@inheritdoc}
   */
  public function getCurrentMicroTime() {
    return $this->decorated->getCurrentMicroTime();
  }

  /**
   * {@inheritdoc}
   */
  public function getRequestTime(): int {
    // @phpstan-ignore-next-line
    if ($faked_date = \Drupal::state()->get('project_browser_test.fake_date_time')) {
      return \DateTime::createFromFormat('U', $faked_date)->getTimestamp();
    }
    return $this->decorated->getRequestTime();
  }

  /**
   * Sets a fake time from an offset that will be used in the test.
   *
   * @param string $offset
   *   A date/time offset string as used by \DateTime::modify.
   */
  public static function setFakeTimeByOffset(string $offset): void {
    $fake_time = (new \DateTime())->modify($offset)->format('U');
    \Drupal::state()->set('project_browser_test.fake_date_time', $fake_time);
  }

}
