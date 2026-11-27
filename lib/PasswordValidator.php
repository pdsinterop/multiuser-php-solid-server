<?php

/*
	Code modified from https://gitlab.com/garybell/password-validation/ (MIT licensed)
*/
namespace Pdsinterop\PhpSolid;

class PasswordValidator
{
	private string $specialCharacters = ' !"#$%&\'()*+,-./:;<=>?@[\]^_{|}~';
	private string $lowercaseCharacters = 'abcdefghijklmnopqrstuvwxyz';
	private string $uppercaseCharacters = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ';
	private string $numbers = '0123456789';

	/**
	 * The maximum number of times the same character can appear in the password
	 * @var int
	 */
	private int $maxOccurrences = 2;

	/**
	 * Get the base amount of characters from the characters used in the password.
	 * This is the number of possible characters to pick from in the used character sets
	 *   i.e. 26 for only lower case passwords
	 * @param $password
	 * @return int
	 */
	public function getBase(string $password): int
	{
		$characters = str_split($password);
		$base = 0;
		$hasSpecial = false;
		$hasLower = false;
		$hasUpper = false;
		$hasDigits = false;

		foreach ($characters as $character) {
			if (!$hasLower && strpos($this->lowercaseCharacters, $character) !== false) {
				$hasLower = true;
				$base += strlen($this->lowercaseCharacters);
			}
			if (!$hasUpper && strpos($this->uppercaseCharacters, $character) !== false) {
				$hasUpper = true;
				$base += strlen($this->uppercaseCharacters);
			}
			if (!$hasSpecial && strpos($this->specialCharacters, $character) !== false) {
				$hasSpecial = true;
				$base += strlen($this->specialCharacters);
			}
			if (!$hasDigits && strpos($this->numbers, $character) !== false) {
				$hasDigits = true;
				$base += strlen($this->numbers);
			}

			if (
				strpos($this->lowercaseCharacters, $character) === false
				&& strpos($this->uppercaseCharacters, $character) === false
				&& strpos($this->specialCharacters, $character) === false
				&& strpos($this->numbers, $character) === false
			) {
				$base++;
			}
		}

		return $base;
	}

	/**
	 * get the calculated entropy of the password based on the rules for excluding duplicate characters
	 * If a password is in the banned list, entropy will be 0.
	 * @see bannedPassords()
	 * @param string $password
	 * @param array $bannedPasswords a custom list of passwords to disallow
	 * @return float
	 */
	public function getEntropy(string $password, array $bannedPasswords = []): float
	{
		if (in_array(strtolower($password), $bannedPasswords)) {
			// these are so weak, we just want to outright ban them. Entropy will be 0 for anything in this list.
			return 0;
		}
		$base = $this->getBase($password);
		$length = $this->getLength($password);

		$decimalPlaces = 2;
		return number_format(log($base ** $length), $decimalPlaces);
	}

	/**
	 * Check the length of the password based on known rules
	 *  Characters will only be counted a maximum of 2 times e.g. aaa has length 2
	 * @param $password
	 * @return int
	 */
	public function getLength(string $password): int
	{
		$usedCharacters = [];
		$characters = str_split($password);
		$length = 0;

		foreach ($characters as $character) {
			if (array_key_exists($character, $usedCharacters) && $usedCharacters[$character] < $this->maxOccurrences) {
				$length++;
				$usedCharacters[$character]++;
			}
			if (!array_key_exists($character, $usedCharacters)) {
				$usedCharacters[$character] = 1;
				$length++;
			}
		}

		return $length;
	}
}
