<?php
	include('vendor/autoload.php');
	require 'dbconfig.php';
	require 'keyboards.php';
	require 'BotHelper.php';
	$telegram = new Longman\TelegramBot\Telegram($telegram_key, '@apmstore_bot');
	/*
	$result = FindItem('judd');
	echo $result;
	exit();
	$json = json_decode($result, true);
	echo $result;
	$mainproducts = array_values($json["judd_code"]['mainProducts']);
	$analogs = array_values($json["judd_code"]['analogProducts']);
	echo count($analogs);
	usort($analogs, function ($a, $b) {
		return $a['price_info'] - $b['price_info'];
	}); 
	$pr = 1;
	$message = "";
	$mainproducts = array_values($mainproducts);
	$newmainproducts = array();
	$stockitems = GetStockList();
	for ($i = 0; $i < count($stockitems); $i++)
	{
		for ($z = 0; $z < count($analogs); $z++)
		{
			if ($analogs[$z]['priceName'] == $stockitems[$i]->Name)
			{
				$newmainproducts[] = $analogs[$z];
			}
		}
	}
	for ($i = 0; $i < count($analogs); $i++)
	{
		$isinlist = false;
		for ($z = 0; $z < count($stockitems); $z++)
		{
			if ($analogs[$i]['priceName'] == $stockitems[$i]->Name)
			{
				$isinlist = true;
				break;
			}
		}
		if (!$isinlist)
		{
			$newmainproducts[] = $analogs[$i];
		}
	}
	$analogs = $newmainproducts;
	foreach ($analogs as $item)
	{
		$sp = explode('/', $item['delivery'])[0];
		$message .= "*Предложение $pr:*\n" .
			"Название: " . $item['name'] . "\n" .
			"Производитель: " . $item['make'] . "\n" .
			"Артикул: " . $item['code'] . "\n" .
			"Срок поставки: " . $item['delivery'] . " (дни)\n" .
			"Склад: " . $item['priceName'] . "\n" .
			"Цена: " . $item['price_info'] . ' ' . $item['currency'] . "\n\n";
		$pr++;
	}
	echo $message;
	exit();
	*/
	$telegram->useGetUpdatesWithoutDatabase();
	$result = $telegram->handle();
	$updatetype = $result->getUpdateType();
	$chat_id = GetChatID($result);
	$user = GetUser($chat_id);
	if ($updatetype == 'message')
	{
		$text = $result->getMessage()->getText();
		$name = $result->getMessage()->getFrom()->getUsername();
		if ($text == '/start')
		{
			$queryresult = $db_con->prepare("select * from Users where `ID`=:id limit 1");
			$queryresult->execute(array(":id" => $chat_id));
			if ($queryresult->rowCount() == 0)
			{
				$stmt = $db_con->prepare("INSERT INTO Users(ID,RequestSent,Enter_Time) VALUES(:id,:reqsent,:entertime)");
				$stmt->execute(array(
					":id"        => $chat_id,
					":reqsent"   => 1,
					":entertime" => (new DateTime('now'))->format('Y-m-d H:i:s')
				));
			}
			Longman\TelegramBot\Request::sendMessage([
				'chat_id'      => $chat_id,
				'text'         => "Здравствуйте! Я могу вам помочь:\n1. Подобрать и купить запчасти\n2. Зарегистрироваться на сайте\n3. Получить консультацию",
				'reply_markup' => $mainkeyboard
			]);
		}
		else if (strpos($text, '/SetUserMaxAnalogsLimitAD') !== false)
		{
			$text = substr($text, 26);
			if (is_numeric($text))
			{
				$queryresult = $db_con->prepare("update Configs set `ParamValue`=:value where `ParamKey`=:key");
				$queryresult->execute(array(
					":key"   => 'AnalogsLimit',
					':value' => $text
				));
				Longman\TelegramBot\Request::sendMessage([
					'chat_id' => $chat_id,
					'text'    => "Максимальное количество отображаемых аналогов изменено!",
				]);
			}
			else
			{
				Longman\TelegramBot\Request::sendMessage([
					'chat_id' => $chat_id,
					'text'    => "Неверный формат!",
				]);
			}
		}
		else if ($text == '/EditStockMenuAD')
		{
			
			Longman\TelegramBot\Request::sendMessage([
				'chat_id'      => $chat_id,
				'text'         => "Меню редактирования складов\nСклады выбранные на данный момент: " . GetStockListAsString(),
				'reply_markup' => $stockmainmenu
			]);
		}
		else if ($text == '®️ Зарегистрироваться на сайте')
		{
			$ikb = new Longman\TelegramBot\Entities\InlineKeyboard([
				[
					'text' => 'Перейти',
					'url'  => 'https://apm.store/site/signup'
				]//Move TO Registration
			]);
			Longman\TelegramBot\Request::sendMessage([
				'chat_id'      => $chat_id,
				'text'         => 'Для регистрации на сайте пройдите по ссылке: https://apm.store/site/signup',
				'reply_markup' => $ikb
			]);
		}
		else if ($text == 'ℹ️ Получить консультацию')
		{
			Longman\TelegramBot\Request::sendMessage([
				'chat_id' => $chat_id,
				'text'    => "Для получения консультации\n- позвоните нам по номеру:\n+38 050 390 27 37\n+38 096 390 27 37\n+38 044 390 27 37\n- напишите на почту:\ninfo@apm.store\nВремя работы:\nПн-Пт 09:00-19:00\nСб 09:00-15:00\n"
			]);
		}
		else if ($text == "🔎 Поиск по артикулу")
		{
			Longman\TelegramBot\Request::sendMessage([
				'chat_id' => $chat_id,
				'text'    => 'Введите артикул детали'
			]);
			UpdateStep($chat_id, 1);
		}
		else if ($user->Step == 1 || $user->Step == 0)
		{
			$ikb = new Longman\TelegramBot\Entities\InlineKeyboard([
				[
					'text'          => 'Да',
					'callback_data' => 'SANY'
				],
				//Show ANalogs Yes
				[
					'text'          => 'Нет',
					'callback_data' => 'SANN'
				]
				//Show ANalogs No
			]);
			Longman\TelegramBot\Request::sendMessage([
				'chat_id'      => $chat_id,
				'text'         => 'Показать также аналоги?',
				'reply_markup' => $ikb
			]);
			UpdateSingleParam($chat_id, 'Articul', $text);
			UpdateStep($chat_id, 2);
		}
		else if ($user->Step == 2)
		{
			AddItemToStockList($text);
			UpdateStep($chat_id, 0);
			Longman\TelegramBot\Request::sendMessage([
				'chat_id'      => $chat_id,
				'text'         => "Меню редактирования складов\nСклады выбранные на данный момент: " . GetStockListAsString(),
				'reply_markup' => $stockmainmenu
			]);
		}
		else if ($user->Step == 3)
		{
			RemoveItemToStockList($text);
			UpdateStep($chat_id, 0);
			Longman\TelegramBot\Request::sendMessage([
				'chat_id'      => $chat_id,
				'text'         => "Меню редактирования складов\nСклады выбранные на данный момент: " . GetStockListAsString(),
				'reply_markup' => $stockmainmenu
			]);
		}
	}
	else
	{
		$text = $result->getCallbackQuery()->getData();
		$name = $result->getCallbackQuery()->getFrom()->getUsername();
		$messageid = $result->getCallbackQuery()->getMessage()->getMessageId();
		$result->getCallbackQuery()->answer();
		Longman\TelegramBot\Request::deleteMessage([
			'chat_id'    => $chat_id,
			'message_id' => $messageid
		]);
		if ($text == 'SANY')
		{
			UpdateSingleParam($chat_id, 'ShowAnalogs', '1');
			SendMaxDeliverMessage();
		}
		else if ($text == "SANN")
		{
			UpdateSingleParam($chat_id, 'ShowAnalogs', '0');
			SendMaxDeliverMessage();
		}
		else if (strpos($text, 'DLimit_') !== false)
		{
			$type = substr($text, 7);
			$days = 7;
			switch ($type)
			{
				case 'STDDL':
					$days = 3;
					break;
				case 'SSNN':
					$days = 7;
					break;
				case 'STWDDL':
					$days = 14;
					break;
				case 'STFDDL':
					$days = 25;
					break;
			}
			UpdateStep($chat_id, 0);
			$result = FindItem($user->Articul);
			$json = json_decode($result, true);
			$mainproducts = array_values($json[$user->Articul . "_code"]['mainProducts']);
			$analogs = array_values($json[$user->Articul . "_code"]['analogProducts']);
			usort($mainproducts, function ($a, $b) {
				return $a['price_info'] - $b['price_info'];
			});
			usort($analogs, function ($a, $b) {
				return $a['price_info'] - $b['price_info'];
			});
			$pr = 1;
			$FullMessageToSent = "";
			$limit = GetAnalogsLimit();
			if (count($mainproducts) != 0)
			{
				$newmainproducts = array();
				$stockitems = GetStockList();
				for ($i = 0; $i < count($stockitems); $i++)
				{
					for ($z = 0; $z < count($mainproducts); $z++)
					{
						if ($mainproducts[$z]['priceName'] == $stockitems[$i]->Name)
						{
							$newmainproducts[] = $mainproducts[$z];
						}
					}
				}
				for ($i = 0; $i < count($mainproducts); $i++)
				{
					$isinlist = false;
					for ($z = 0; $z < count($stockitems); $z++)
					{
						if ($mainproducts[$i]['priceName'] == $stockitems[$z]->Name)
						{
							$isinlist = true;
							break;
						}
					}
					if (!$isinlist)
					{
						$newmainproducts[] = $mainproducts[$i];
					}
				}
				$mainproducts = $newmainproducts;
				$itemindex = 1;
				foreach ($mainproducts as $item)
				{
					$sp = explode('/', $item['delivery'])[0];
					if ($sp <= $days)
					{
						$name = strlen($item['name']) <= 1 ? '-' : $item['name'];
						$name = str_replace('*', '', $name);
						$delivery = "";
						if ($sp == '0')
						{
							$delivery = "*В наличии*";
						}
						else
						{
							$delivery = 'Срок поставки (дней): ' . $item['delivery'];
						}
						$FullMessageToSent .= "*Предложение $itemindex:*\n" .
							"Название:" . " $name " . "\n" .
							"Производитель: " . $item['make'] . "\n" .
							"Артикул: " . $item['code'] . "\n" .
							$delivery . "\n" .
							"Склад: " . $item['priceName'] . "\n" .
							"Цена: " . $item['price_info'] . ' ' . $item['currency'] . "\n\n";
						$itemindex++;
						
					}
					$pr++;
					if (strlen($FullMessageToSent) >= 1750 || $itemindex == $limit + 1 || $pr == count($mainproducts) + 1)
					{
						Longman\TelegramBot\Request::sendMessage([
							'chat_id'    => $chat_id,
							'text'       => $FullMessageToSent,
							'parse_mode' => 'Markdown'
						]);
						$FullMessageToSent = "";
					}
					if ($itemindex == $limit + 1)
					{
						break;
					}
				}
			}
			else
			{
				$FullMessageToSent = '*Товары по данному артикулу не найдены!*';
				Longman\TelegramBot\Request::sendMessage([
					'chat_id'    => $chat_id,
					'text'       => $FullMessageToSent,
					'parse_mode' => 'Markdown',
				]);
			}
			if ($user->ShowAnalogs == 1)
			{
				$pr = 1;
				$itemindex = 1;
				$FullMessageToSent = "";
				if (count($analogs) != 0)
				{
					if (count(GetStockList()) != 0)
					{
						$newanalogs = array();
						$stockitems = GetStockList();
						for ($i = 0; $i < count($stockitems); $i++)
						{
							for ($z = 0; $z < count($analogs); $z++)
							{
								if ($analogs[$z]['priceName'] == $stockitems[$i]->Name)
								{
									$newanalogs[] = $analogs[$z];
								}
							}
						}
						for ($i = 0; $i < count($analogs); $i++)
						{
							$isinlist = false;
							for ($z = 0; $z < count($stockitems); $z++)
							{
								if ($analogs[$i]['priceName'] == $stockitems[$z]->Name)
								{
									$isinlist = true;
									break;
								}
							}
							if (!$isinlist)
							{
								$newanalogs[] = $analogs[$i];
							}
						}
						$analogs = $newanalogs;
					}
					foreach ($analogs as $item)
					{
						$sp = explode('/', $item['delivery'])[0];
						if ($sp <= $days)
						{
							$name = strlen($item['name']) <= 1 ? '-' : $item['name'];
							$name = str_replace('*', '', $name);
							$delivery = "";
							if ($sp == '0')
							{
								$delivery = "*В наличии*";
							}
							else
							{
								$delivery = 'Срок поставки (дней): ' . $item['delivery'];
							}
							$FullMessageToSent .= "*Предложение(Аналог) $itemindex:*\n" .
								"Название:" . " $name " . "\n" .
								"Производитель: " . $item['make'] . "\n" .
								"Артикул: " . $item['code'] . "\n" .
								$delivery . "\n" .
								"Склад: " . $item['priceName'] . "\n" .
								"Цена: " . $item['price_info'] . ' ' . $item['currency'] . "\n\n";
							$itemindex++;
						}
						$pr++;
						if (strlen($FullMessageToSent) >= 1750 || $itemindex == $limit + 1 || $pr == count($analogs) + 1)
						{
							$message = Longman\TelegramBot\Request::sendMessage([
								'chat_id'    => $chat_id,
								'text'       => $FullMessageToSent,
								'parse_mode' => 'Markdown'
							]);
							$FullMessageToSent = "";
						}
						if ($itemindex == $limit + 1)
						{
							break;
						}
					}
				}
				else if (count($mainproducts) != 0)
				{
					$FullMessageToSent = "*Не найдено аналогов для данного артикула!*";
					Longman\TelegramBot\Request::sendMessage([
						'chat_id'      => $chat_id,
						'text'         => $FullMessageToSent,
						'parse_mode'   => 'Markdown',
						'reply_markup' => $mainkeyboard
					]);
				}
			}
			if ((count($analogs) != 0 && $user->ShowAnalogs == 1) || count($mainproducts) != 0)
			{
				$ikb = new Longman\TelegramBot\Entities\InlineKeyboard([
					[
						'text' => 'Зарегистрироваться',
						'url'  => 'https://apm.store/site/signup'
					]//Move TO Registration
				]);
				Longman\TelegramBot\Request::sendMessage([
					'chat_id'      => $chat_id,
					'text'         => 'Цена без учета скидки.Стоимость доставки включена в стоимость у складов: UA, ORD, СRD , по остальным складам рассчитывается индивидуально. Зарегистрируйтесь и получите скидку сейчас:  регистрация https://apm.store/site/signup',
					'parse_mode'   => 'Markdown',
					'reply_markup' => $mainkeyboard,
					'reply_markup' => $ikb
				]);
			}
			IncrementOperationCount($chat_id);
			
		} //запрос к апи и вывод результата
		else if ($text == 'ASTPL')
		{
			UpdateStep($chat_id, 2);
			Longman\TelegramBot\Request::sendMessage([
				'chat_id' => $chat_id,
				'text'    => "Введите название склада,который хотите добавить"
			]);
		}
		else if ($text == 'RFSPL')
		{
			UpdateStep($chat_id, 3);
			Longman\TelegramBot\Request::sendMessage([
				'chat_id' => $chat_id,
				'text'    => "Введите название склада,который хотите удалить\nСклады добавленные на данный момент: " . GetStockListAsString()
			]);
		}
		else if ($text == 'GBTSM')
		{
			Longman\TelegramBot\Request::sendMessage([
				'chat_id'      => $chat_id,
				'text'         => "Меню редактирования складов\nСклады выбранные на данный момент: " . GetStockListAsString(),
				'reply_markup' => $stockmainmenu
			]);
		}
	}
