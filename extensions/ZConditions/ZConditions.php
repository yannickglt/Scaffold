<?php
/**
 * conditions
 *
 * Allows you to use SASS-style conditions, essentially assigning classes
 * to selectors from within your css. You can also pass arguments through
 * to the condition.
 *
 * @package 		Scaffold
 * @author 			Anthony Short <anthonyshort@me.com>
 * @copyright 		2009-2010 Anthony Short. All rights reserved.
 * @license 		http://opensource.org/licenses/bsd-license.php  New BSD License
 * @link 			https://github.com/anthonyshort/csscaffold/master
 */
class Scaffold_Extension_ZConditions extends Scaffold_Extension
{
	/**
	 * Stores the conditions for debugging purposes
	 * @var array
	 */
	public $conditions = array();

	/**
	 * @param $source
	 * @param $scaffold
	 * @return void
	 */
	public function process($source,$scaffold)
	{	
		
		// Find any conditions
		if(preg_match_all('/\@if\s+(\((.*?)\))? \s* \{/sx',$source->contents,$conditions))
		{
			
			$css = $source->contents;
						
			foreach($conditions[0] as $key => $condition)
			{
				$_result = 1;
				
				// Position of the condition in the CSS
				$condition_start = strpos($css,$condition);
				
				// The position of the opening brace
				$start = $condition_start + strlen($condition) - 1;
				
				// Get the content within the braces
				$content = $scaffold->helper->string->match_delimiter('{','}',$start,$css);
				
				// The content without the braces
				$inner_content = trim($content,'{} ');
				
				// Parse the params
				if((count($conditions) > 2) && ($conditions[2][$key] != false))
				{
					$params = explode(',',$conditions[2][$key]);
					
					foreach($params as $param)
					{
						try 
						{
							eval('$_result &= (' . $this->escapeOperands($param) . ');');
						} catch(Exception $e) {}
					}
				}
				
				if ($_result) {
					
					// The content without the braces
					$inner_content = trim($content,'{} ');
					
					// Remove it from the CSS
					$css = substr_replace($css, $inner_content, $condition_start, strlen($content) + strlen($condition) - 1 );
					
					$elseSearch = trim(substr($css, $condition_start + strlen($inner_content)));
				}
				else {

					// Remove it from the CSS
					$css = substr_replace($css, '', $condition_start, strlen($content) + strlen($condition) - 1 );
					
					$elseSearch = trim(substr($css, $condition_start));
				}
				
				while(preg_match_all('/^\@elseif\s+(\((.*?)\))? \s* \{/sx',$elseSearch,$conditionsElseIf))
				{

					foreach($conditionsElseIf[0] as $key => $condition)
					{
						
						// Position of the condition in the CSS
						$condition_start = strpos($css,$condition);
						
						// The position of the opening brace
						$start = $condition_start + strlen($condition) - 1;

						// Get the content within the braces
						$content = $scaffold->helper->string->match_delimiter('{','}',$start,$css);
						
						if ($_result) {
							$css = substr_replace($css, '', $condition_start, strlen($content) + strlen($condition) - 1 );
							$elseSearch = trim(substr($css, $condition_start));
						}
						else {
							
							if((count($conditionsElseIf) > 2) && ($conditionsElseIf[2][$key] != false))
							{
								$_result = 1;
			
								$params = explode(',',$conditionsElseIf[2][$key]);
								
								foreach($params as $param_key => $param)
								{
									try {
										eval('$_result &= (' . $this->escapeOperands($param) . ');');
									} catch(Exception $e) {}
								}
								
							}
							
							if ($_result) {

								// The content without the braces
								$inner_content = trim($content,'{} ');

								$css = substr_replace($css, $inner_content, $condition_start, strlen($content) + strlen($condition) - 1 );
								
								$elseSearch = trim(substr($css, $condition_start + strlen($inner_content)));
							}
							else {
								$css = substr_replace($css, '', $condition_start, strlen($content) + strlen($condition) - 1 );
								$elseSearch = trim(substr($css, $condition_start));
							}
						}
					}
				}
				
				// Find any conditions
				if(preg_match_all('/^\@else\s+ \s* \{/sx',$elseSearch,$conditionsElse))
				{
					
					foreach($conditionsElse[0] as $key => $condition)
					{
						
						// Position of the condition in the CSS
						$condition_start = strpos($css,$condition);
						
						// The position of the opening brace
						$start = $condition_start + strlen($condition) - 1;

						// Get the content within the braces
						$content = $scaffold->helper->string->match_delimiter('{','}',$start,$css);
						
						if (!$_result) {
							
							// The content without the braces
							$inner_content = trim($content,'{} ');
							
							// Remove it from the CSS
							$css = substr_replace($css, $inner_content, $condition_start, strlen($content) + strlen($condition) - 1 );
						}
						else {

							// Remove it from the CSS
							$css = substr_replace($css, '', $condition_start, strlen($content) + strlen($condition) - 1 );
						}
						
					}
				}
				 
			}
			
			$source->contents = $css;
		}
		
	}
	
	private function escapeOperands($operation) {
		
		// Remove existing quotes and spaces
		$operation = str_replace(' ', '', str_replace('\'', '', $operation));
		return '\'' . str_replace('>', '\'>\'', str_replace('<', '\'<\'', str_replace('!=', '\'!=\'', str_replace('>=', '\'>=\'', str_replace('<=', '\'<=\'', str_replace('==', '\'==\'', str_replace('===', '\'===\'', $operation))))))) . '\'';
	}
}