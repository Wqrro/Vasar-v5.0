<?php

declare(strict_types=1);

namespace Warro\forms;

use pocketmine\player\Player;

class EmptyForm extends Form
{

	protected array $data = [];

	private string $title;
	private string $description;

	public function __construct(string $title, string $description)
	{
		parent::__construct(null);
		$this->title = $title;
		$this->description = $description;
	}

	public function openForm(Player $player)
	{
		$this->data = [];
		$this->data['type'] = 'form';
		$this->data['title'] = $this->title;
		$this->data['content'] = $this->description;
		$this->data['buttons'] = [];
		$player->sendForm($this);
	}

	public function handleResponse(Player $player, $data): void
	{
	}

	public function jsonSerialize()
	{
		return $this->data;
	}
}
