<?php

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
	
	foreach ($TData as $diff_file => &$Tab)
	{
		$TLine[] = _getModificationOfFile($Tab, $diff_file);
	}
$t1 = microtime(true);
	_printTLine($TLine); 
	
$t2 = microtime(true);	
	fclose($handle);
}



print '<br /><br /><br /><p>';
print $t1.'<br />';
print $t2.'<br />';
print ($t2-$t1).'<br /> PHF</p>';

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
			// @@ -47,7 +47,8 @@ $specialtostring=array(0=>'common', 1=>'interfaces', 2=>'other', 3=>'functional'
			$indice_line_modified = htmlentities($line);
			$TRes[$title][$indice_line_modified] = array();
		}
		else 
		{
			$line = htmlentities($line);
			$line = str_replace(' ', '&nbsp;', $line);
			
			// TODO à perfectionner car les lignes modifiés ne sont pas au même niveau en affichage 
			// @@ -72,8 +76,19 @@ 
			if ($line[0] == '-')
			{
				$TRes[$title][$indice_line_modified][] = array('a' => $line, 'b' => '');
			}
			elseif ($line[0] == '+')
			{
				$TRes[$title][$indice_line_modified][] = array('a' => '', 'b' => $line);
			}
			else
			{
				$TRes[$title][$indice_line_modified][] = array('a' => $line, 'b' => $line);
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
							
							if (empty($TVal['a'])) { $class_td_a .= ' empty_cell'; 		$class_td_b .= ' line_added';  	$class_line_number_b = ' line_num_added'; }
							if (empty($TVal['b'])) { $class_td_a .= ' line_deleted'; 	$class_td_b .= ' empty_cell'; 	$class_line_number_a = ' line_num_deleted'; }
							
							$str .= '<tr>';
							$str .= '<td class="line_number'.$class_line_number_a.'">&nbsp;</td><td class="'.$class_td_a.'">'.$TVal['a'].'</td>';
							$str .= '<td class="line_number separate_line'.$class_line_number_b.'">&nbsp;</td><td class="'.$class_td_b.'">'.$TVal['b'].'</td>';
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
