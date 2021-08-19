<?php

class VisualRegressCest {

	public function _before(AcceptanceTester $I) {
		//do nothing
	}

	/**
	 * @dataProvider pageProvider
	 * @param AcceptanceTester $I
	 * @param Example $example
	 */
	public function tryToTest(AcceptanceTester $I, \Codeception\Example $example) {
		$I->addPageToOutput($example);
		$I->amOnPage($example['url']);
		$I->wait(3);
		$I->preComment($example['name'], 'Страница возвращает 404 ошибку');
		$I->dontSee('Not Found', 'h1');
		$I->preCommentVisualDeviation($example['name']);
		$I->dontSeeVisualChanges("page", 'body');
		$I->clearPreComments($example['name']);
	}

	public function _failed(AcceptanceTester $I, \Codeception\Example $example) {

	}

	public function _passed(AcceptanceTester $I, \Codeception\Example $example) {

	}

	/**
	 * @return array
	 */
	protected function pageProvider()
	{
		return [
			['url' => '/examples/dashboard.html', 'name' => 'Dashboard'],
			['url' => '/examples/user.html', 'name' => 'Профиль'],
			['url' => '/examples/typography.html', 'name' => 'Текст'],
			['url' => '/examples/notifications.html', 'name' => 'Уведомления'],
			['url' => '/examples/tables.html', 'name' => 'Таблица'],
			['url' => '/examples/none.html', 'name' => 'Несуществующая страница'],
			['url' => '/examples/gone.html', 'name' => 'Пропавшая страница']
		];
	}
}
