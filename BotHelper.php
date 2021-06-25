<?php
	/**
	 * Created by PhpStorm.
	 * User: Luka
	 * Date: 10.04.2019
	 * Time: 23:10
	 */
	function SendMaxDeliverMessage()
	{
		global $chat_id;
		$ikb = new Longman\TelegramBot\Entities\InlineKeyboard([
			[
				'text'          => '3 дня',
				'callback_data' => 'DLimit_STDDL'//Set Three Day Delivery Limit
			],
			[
				'text'          => '7 дней',
				'callback_data' => 'DLimit_SSNN'//Set Seven Day Delivery Limit
			]
		]);
		$ikb->addRow(
			[
				'text'          => '14 дней',
				'callback_data' => 'DLimit_STWDDL'
			],//Set Two Week Day Delivery Limit
			[
				'text'          => '25 дней',
				'callback_data' => 'DLimit_STFDDL'
			]//Set Twenty Five Day Delivery Limit
		);
		Longman\TelegramBot\Request::sendMessage([
			'chat_id'      => $chat_id,
			'text'         => 'Максимальный срок поставки?',
			'reply_markup' => $ikb
		]);
		UpdateStep($chat_id, 3);
	}
	function FindItem($articul)
	{
		$data = '{"name":"telegram@apm.store","token":"NK44CVCpqPKnKoeZNjfkivQFdWpLgO7E","code":"' . $articul . '","analogs":true}';
		$ch = curl_init('https://api.apm.store/product/search');
		curl_setopt($ch, CURLOPT_POST, true);
		curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
		curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
		curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
		curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type: application/json'));
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		$json = curl_exec($ch);
		curl_close($ch);
		return $json;
	}
	function UpdateStep($id, $step)
	{
		global $db_con;
		$queryresult = $db_con->prepare("update Users set `Step`=:step where `ID`=:id");
		$queryresult->execute(array(
			":id"   => $id,
			':step' => $step
		));
	}
	function UpdateSingleParam($id, $param, $value)
	{
		global $db_con;
		$queryresult = $db_con->prepare("update Users set `" . $param . "` = :val where `ID`=:id");
		$queryresult->execute(array(
			":id"  => $id,
			':val' => $value
		));
	}
	function GetUser($id)
	{
		global $db_con;
		$queryresult = $db_con->prepare("select * from Users where `ID`=:id");
		$queryresult->execute(array(":id" => $id));
		if ($queryresult->rowCount() == 0)
			return null;
		return $queryresult->fetch(PDO::FETCH_OBJ);
	}
	function GetAnalogsLimit()
	{
		global $db_con;
		$queryresult = $db_con->prepare("select * from Configs where `ParamKey`=:key");
		$queryresult->execute(array(":key" => 'AnalogsLimit'));
		return $queryresult->fetch(PDO::FETCH_OBJ)->ParamValue;
	}
	function GetChatID($result)
	{
		$updatetype = $result->getUpdateType();
		$chat_id = '';
		if ($updatetype == 'message')
		{
			$chat_id = $result->getMessage()->getChat()->id;
		}
		else if ($updatetype == 'callback_query')
		{
			$chat_id = $result->getCallbackQuery()->getFrom()->getId();
		}
		return $chat_id;
	}
	function GetStockList()
	{
		global $db_con;
		$queryresult = $db_con->prepare("select * from Stocks");
		$queryresult->execute();
		$items = $queryresult->fetchAll(PDO::FETCH_OBJ);
		return $items;
	}
	function GetStockListAsString()
	{
		$items = GetStockList();
		$message = '';
		foreach ($items as $item)
		{
			$message .= $item->Name . ' , ';
		}
		if (count($items) != 0)
		{
			$message = substr($message, 0, -2);
		}
		return $message;
	}
	function AddItemToStockList($item)
	{
		global $db_con;
		$stmt = $db_con->prepare("INSERT INTO Stocks(Name) VALUES(:name)");
		$stmt->execute(array(
			":name" => $item
		));
	}
	function RemoveItemToStockList($item)
	{
		global $db_con;
		$stmt = $db_con->prepare("Delete from Stocks where `Name`=:name");
		$stmt->execute(array(
			":name" => $item
		));
	}
	function IncrementOperationCount($chatid)
	{
		global $db_con;
		$stmt = $db_con->prepare("UPDATE `Users` SET RequestSent=RequestSent+:mvalue WHERE ID=:id");
		$stmt->execute(array(":id" => $chatid, ':mvalue' => 1));
	}