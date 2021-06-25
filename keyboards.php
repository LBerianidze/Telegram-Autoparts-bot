<?php
	/**
	 * Created by PhpStorm.
	 * User: Luka
	 * Date: 10.04.2019
	 * Time: 10:08
	 */
	$mainkeyboard = new Longman\TelegramBot\Entities\Keyboard(
		[
			'🔎 Поиск по артикулу',
		],
		[
			'®️ Зарегистрироваться на сайте'
		],
		[
			'ℹ️ Получить консультацию'
		]
	);
	$mainkeyboard->setResizeKeyboard(true);
	$mainkeyboard->setOneTimeKeyboard(false);
	$mainkeyboard->setSelective(false);
	
	$stockmainmenu = new Longman\TelegramBot\Entities\InlineKeyboard([
		[
			'text'          => 'Добавить склад в список приоритетов',
			'callback_data' => 'ASTPL'//Add Stock To Priority List
		],
	]);
	$stockmainmenu->addRow(
		[
			'text'          => 'Удалить склад из списка приоритетов',
			'callback_data' => 'RFSPL'//Add From Stock Priority List
		]
	);
	$stockmainmenu->addRow(
		[
			'text'          => 'Выйти',
			'callback_data' => 'Exit'//Add From Stock Priority List
		]
	);