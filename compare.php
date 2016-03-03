<?php
/*
 * Copyright (C) 2016 Pierre-Henry Favre <phf@atm-consulting.fr>
 *
 * This program and files/directory inner it is free software: you can
 * redistribute it and/or modify it under the terms of the
 * GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 */
 
$srcFile = '/var/www/html/compare/test.diff';

$handle = fopen($srcFile, 'r');
$branch_a = array();
$branch_b = array();
$TData = $TLine = array();

if ($handle)
{
	$i=0;
	$last_new_file = '';
	while ($line = fgets($handle))
	{
		if (substr($line, 0, 4) == 'diff')
		{
			$last_new_file = $line;
			$TData[$last_new_file] = array();
		}
		else {
			$TData[$last_new_file][] = $line;
		}
		
	}

	//$t1 = microtime(true);	
	foreach ($TData as $diff_file => &$Tab)
	{
		$TLine[] = _getModificationOfFile($Tab, $diff_file);
	}

	//$t2 = microtime(true);	
	_printTLine($TLine); 
	
	fclose($handle);
}

function _getModificationOfFile(&$Tab, $diff_file)
{
	$TRes = array();
	$title = _cleanTitle($diff_file);

	foreach ($Tab as $i => &$line)
	{
		if ($i <= 2) continue; // ignore
		if (($line[0] == '-' && $line[1] == '-' && $line[2] == '-') || ($line[0] == '+' && $line[1] == '+' && $line[2] == '+')) continue;
		
		if ($line[0] == '@' && $line[1] == '@')
		{
			$index = 0;
			$index_start_delete = null;
			
			$indice_line_modified = htmlentities($line);
			$TRes[$title][$indice_line_modified] = array();
			
			// @@ -47,7 +47,8 @@ $specialtostring=array(0=>'common', 1=>'interfaces', 2=>'other', 3=>'functional'
			$str = substr($line, 3, strpos($line, '@@', 2)-3); // Récupération de : [-47,7 +47,8]
			preg_match_all('/(\+|-)[0-9]*/', $line, $TMatch); // $TMatch[0] = array(-47, +47)
			
			$line_number_a = abs($TMatch[0][0]);
			$line_number_b = abs($TMatch[0][1]);
		}
		else 
		{
			$line = htmlentities($line);
			
			if ($line[0] == '-') // Ligne supprimée sur branch A
			{
				$line = substr($line, 1);
				if (is_null($index_start_delete)) $index_start_delete = $index;
				$TRes[$title][$indice_line_modified][$index] = array('line_number_a' => $line_number_a, 'line_number_b' => '', 'a' => $line, 'b' => '', 'line_deleted' => true);
				$line_number_a++;
				$index++;
			}
			elseif ($line[0] == '+') // Ligne ajoutée sur branch B
			{
				$line = substr($line, 1);
				if (is_null($index_start_delete)) $index_start_delete = $index;
				if (!isset($TRes[$title][$indice_line_modified][$index_start_delete]['a']))
				{
					$TRes[$title][$indice_line_modified][$index_start_delete]['line_number_a'] = '';
					$TRes[$title][$indice_line_modified][$index_start_delete]['a'] = '';
				}

				$TRes[$title][$indice_line_modified][$index_start_delete]['line_number_b'] = $line_number_b;
				$TRes[$title][$indice_line_modified][$index_start_delete]['b'] = $line;
				$TRes[$title][$indice_line_modified][$index_start_delete]['line_added'] = true;
				
				$line_number_b++;
				$index_start_delete++;
				
				if ($index <= $index_start_delete) $index++;
			}
			else // Ligne commune
			{
				$index_start_delete = null;
				$TRes[$title][$indice_line_modified][$index] = array('line_number_a' => $line_number_a, 'line_number_b' => $line_number_b, 'a' => $line, 'b' => $line);
				$line_number_a++;
				$line_number_b++;
				$index++;
			}
			
		}
	}
	
	return $TRes;
}

function _cleanTitle($diff_file)
{
	$diff_file = trim(substr($diff_file, 10));
	$diff_file = explode(' ', $diff_file);
	$diff_file = substr($diff_file[0], 1);
	
	return htmlentities($diff_file);
}

function _printTLine(&$TLine)
{
	_printHeader();
	
	$str = '<div id="content">';
	foreach ($TLine as $k => &$Tab)
	{
		$str .= '<div class="diff_file">';
			
			foreach ($Tab as $title => &$T) 
			{
				$str .= '<div class="diff_title">'.$title.'</div>';
				
				$nb_sub_title = count($T);
				$i=0;
				foreach ($T as $sub_title => &$TBranch)
				{
					$i++;
					$str .= '<div class="diff_data">'; 
					
					$str .= '<table>';
					$str .= '<tr class="diff_sub_title"><td colspan="1" class="line_number">&nbsp;</td><td colspan="3">'.$sub_title.'</td></tr>';
					
					if (!empty($TBranch))
					{
						foreach ($TBranch as &$TVal)
						{
							$class_line_number_a = '';
							$class_td_a = 'branch_a';
							$class_line_number_b = '';
							$class_td_b = 'branch_b';
							
							$sign_a = '';
							$sign_b = '';
							
							if (!empty($TVal['line_deleted'])) { $sign_a = '-'; $class_td_a .= ' line_deleted'; $class_line_number_a = ' line_num_deleted'; }
							if (!empty($TVal['line_added'])) { $sign_b = '+'; $class_td_b .= ' line_added';  	$class_line_number_b = ' line_num_added'; }
							
							if (empty($TVal['a'])) { $class_line_number_a .= ' empty_cell'; $class_td_a .= ' empty_cell'; }
							if (empty($TVal['b'])) { $class_line_number_b .= ' empty_cell'; $class_td_b .= ' empty_cell'; }
							
							$str .= '<tr>';
							$str .= '<td class="line_number'.$class_line_number_a.'" data-line-number="'.$TVal['line_number_a'].'" ></td><td class="'.$class_td_a.'"><span class="sign" data-line-sign="'.$sign_a.'"></span>'.$TVal['a'].'</td>';
							$str .= '<td class="line_number separate_line'.$class_line_number_b.'" data-line-number="'.$TVal['line_number_b'].'" ></td><td class="'.$class_td_b.'"><span class="sign" data-line-sign="'.$sign_b.'"></span>'.$TVal['b'].'</td>';
							$str .= '</tr>';
						}

					}
					
					if ($nb_sub_title == $i) $str .= '<tr class="diff_sub_title_end"><td colspan="1" class="line_number">&nbsp;</td><td colspan="3">&nbsp;</td></tr>';
					$str .= '</table>';
					$str .= '</div>';
				}
			}
		$str .= '</div>';
	}
	$str.= '</div>';
	echo $str;
}

function _printHeader()
{
	?>
<!DOCTYPE html>
<html>
<head>
<title>Git diff - compare</title>
<link rel="stylesheet" type="text/css" href="compare.css">
</head>
<body>
	<?php
}

function _printFooter()
{
	?>
</body>
</html>
	<?php
}
