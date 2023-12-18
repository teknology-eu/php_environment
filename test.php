<?php

function asciidoc_elemnts($input) {
  return new_line($input);
}

function new_line($lines) {
  $search_lines = str_replace("\r\n", "\n", $lines);
  $linesArray = explode("\n", $search_lines);

  // Make an array of lines
  $trimmedLinesArray = array_map(function($line) {
      return ltrim($line);
  }, $linesArray);

  // Remove empty lines
  $trimmedLinesArray = array_filter($trimmedLinesArray, function($line) {
      return $line !== '';
  });

  return $trimmedLinesArray;
}

// function html_table($input, $cols, $del) {
//   $lines = asciidoc_elemnts($input);

//   // Splitting the column classes into an array or defaulting to an empty array
//   $instr = $cols ? explode(',', $cols) : [];
//   $arrayLength = count($instr); // The length of your classes array

//   $indentLevel = 0;
//   $indent = function($level) { return str_repeat('  ', $level); }; // 2 spaces for each level

//   $html = "<table class=\"tableblock frame-all grid-all stretch\">\n";
//   $indentLevel++;
//   $html .= $indent($indentLevel) . "<colgroup>\n";
//   $indentLevel++;
//   for ($i = 0; $i < ($cols ? count($instr) : substr_count(reset($lines), $del)); $i++) {
//       $html .= $indent($indentLevel) . "<col style=\"width: " . (100 / max(count($instr), 1)) . "%;\">\n";
//   }
//   $indentLevel--;
//   $html .= $indent($indentLevel) . "</colgroup>\n";
//   $html .= $indent($indentLevel) . "<tbody>\n";
//   $indentLevel++;

//   $counter = 0;

//   foreach ($lines as $line) {
//       if (strpos($line, $del) === 0) {
//           $html .= $indent($indentLevel) . "<tr>\n";
//           $indentLevel++;
//           $cells = explode($del, $line);
//           array_shift($cells); // Remove first empty element due to leading delimiter         

//           foreach ($cells as $cell) {
//               $classAttr = '';
//               if ($arrayLength > 0) {
//                   $currentClass = $instr[$counter % $arrayLength];
//                   $classAttr = " class=\"$currentClass\"";
//                   $counter++; // Increment the counter for each cell
//               }
//               $html .= $indent($indentLevel) . "<td$classAttr><p>" . trim($cell) . "</p></td>\n";
//           }
//           $indentLevel--;
//           $html .= $indent($indentLevel) . "</tr>\n";
//       }
//   }

//   $indentLevel--;
//   $html .= $indent($indentLevel) . "</tbody>\n";
//   $indentLevel--;
//   $html .= $indent($indentLevel) . "</table>";

//   return $html;
// }


function formatHtml($html) {
  $dom = new DOMDocument();
  // Load as a fragment
  @$dom->loadHTML($html, LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD);

  return formatNode($dom->documentElement);
}

function formatNode(DOMNode $node, $depth = 0) {
  $indentation = str_repeat("  ", $depth);
  $formattedHtml = '';

  foreach ($node->childNodes as $child) {
      if ($child->nodeType == XML_ELEMENT_NODE) {
          // Start tag with new line and indentation
          $formattedHtml .= "\n" . $indentation . '<' . $child->nodeName . '>';

          // Recurse into child nodes
          $formattedHtml .= formatNode($child, $depth + 1);

          // End tag with new line and indentation
          $formattedHtml .= "\n" . $indentation . '</' . $child->nodeName . '>';
      } elseif ($child->nodeType == XML_TEXT_NODE) {
          // Text content with trimming and indentation
          $trimmedText = trim($child->nodeValue);
          if (!empty($trimmedText)) {
              $formattedHtml .= "\n" . $indentation . '  ' . htmlspecialchars($trimmedText);
          }
      }
      // ... handle other node types like comments, etc.
  }

  return $formattedHtml;
}


function html_table($input, $cols, $del) {
  // Convert plane text into cells
  $content = str_replace("\n", "", $input);
  $raw_cells = explode('|', $content);
  $cells = array_map('trim', $raw_cells);

  // Check if $cols is set and not empty
  if (!empty($cols)) {
    // Explode $cols into an array if it's set
    $instr = explode(',', $cols);
    $cols = count($instr);
  } else {
    // If $cols is not set, count the '|' characters in the first row of $lines
    // Make sure to check if $lines is not empty and has at least one row
    if (isset($content)) {
      $instr = ['default'];
      $cols = substr_count($content[0], '|');
    } else {
        // Handle the case where $lines is empty or does not have a first row
        $instr = ['normal'];
        $cols = 1; // Or any other default or error handling you prefer
    }
  }
 
  // Define cells kinds
  foreach ($cells as $i => $c) {
    if (empty($c)) continue;
    // Use modulo to cycle through elements of $n
    $alt = $instr[$i % $cols];
    
    // Append to HTML string
    $html_cells[] = "<td class='".$alt."'>".$c."</td>";
  }

  // Split the cells into rows
  $splitCells = array_chunk($html_cells, $cols);

  $html_rows = '';
  foreach ($splitCells as $row) {
     $html_rows .= "<tr>".implode("", $row)."</tr>";
  }

  return formatHtml($html_rows);
}

// function test() {
//  $input = trim('
//  | Col1 | Col2
//  | Cel1 | Cel2
//  | Cel3 | Cel4
//  ');
//  $output = trim('
// <table class="tableblock frame-all grid-all stretch">
//   <colgroup>
//     <col style="width: 50%;">
//     <col style="width: 50%;">
//   </colgroup>
//   <tbody>
//     <tr>
//       <td><p>Col1</p></td>
//       <td><p>Col2</p></td>
//     </tr>
//     <tr>
//       <td><p>Cel1</p></td>
//       <td><p>Cel2</p></td>
//     </tr>
//     <tr>
//       <td><p>Cel3</p></td>
//       <td><p>Cel4</p></td>
//     </tr>
//   </tbody>
// </table>
//   ');
//   $cols = '1,a';
//   $del = '|';
//   $result = trim(html_table($input, $cols, $del));

  
//   if ($result === $output) {
//     echo "The ASCII doc input matches the HTML output.\n";
//     echo "Output: " . $result . "\n";
//   } else {
//     echo "The ASCII doc input don't match the HTML output.\n";
//     echo "Expected: " . $output . "\n";
//     echo "Got: " . $result . "\n";
//   }
// }

function test2() {
  $input = trim('
  | Here inline *markup* _text_ is rendered specially | This is a absolutly norma cell
  | In this cell *markup* _text_ is handeled specially | This is another cell | This is unexpected cell
  | Here inline *markup* _text_ is rendered specially | This is astrange cell
  | I am the alone cell
  | I am the last cell
');
  $output = trim('
    <tr>
      <td class="1">
        <p>Here inline *markup* _text_ is rendered specially</p>
      </td>
      <td class="a">
        <p>In this cell *markup* _text_ is handeled specially</p>
      </td>
    </tr>
    <tr>
      <td class="1">
      <p>Here inline *markup* _text_ is rendered specially</p>
      </td>
    </tr>
');
   $cols = '1,a';
   $del = '|';
   $result = trim(html_table($input, $cols, $del));
 
   
   if ($result === $output) {
     echo "The ASCII doc input matches the HTML output.\n";
   } else {
     echo "The ASCII doc input don't match the HTML output.\n";
     echo "Expected: " . $output . "\n";
     echo "Got: " . $result . "\n";
   }
 }

//test();
test2();

?>