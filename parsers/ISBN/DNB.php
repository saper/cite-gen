<?php
/**
 * Nukat parser.
 *
 * @addtogroup Parsers
 * @author Michał "Hołek" Połtyn
 * @copyright © 2009 Michał Połtyn
 * @license GNU General Public Licence 2.0 or later
 */

class DNB extends Parser {

	protected $website;

	private $title;
	private $lastNames = array();
	private $firstNames = array();
	private $date;
	private $publisher;
	private $place;
	private $source;
	private $ISBN;
	
	/**
	 * Array consisting of errors reported on the way
	 * @var array
	 */
	private $errors = array();

    /**
     * Constructor for objects of class isbnDB
     */
	public function __construct($ISBN)
	{
		$fh = fopen($this->source = 'https://portal.d-nb.de/opac.htm?method=showFullRecord&currentPosition=0&currentResultId='.$ISBN.'%2526any','r');
		if ($fh) // if file found
		{
			$foundCOinS = false; // COinS checker
			$text = ''; // text storage
			while (!$foundCOinS && !feof($fh))
			{
				$text .= $line = stream_get_line($fh, 20*1024, 'Ende COinS');
				if (stripos($line, 'Einbindung COinS fuer Zotero'))
				{
					$foundCOinS = true;
				}
			}
			fclose($fh);
			
			if ($foundCOinS)
			{
				preg_match('/<span class="Z3988" title="(.*?)">/is',$text,$COinS);
				$COinS = htmlspecialchars_decode($COinS[1]);
				parse_str($COinS,$COinS);
				$title = explode(' : ',$COinS['rft_title']);
				$this->title = $title[0];
				$this->lastNames[] = $COinS['rft_aulast'];
				$this->firstNames[] = $COinS['rft_aufirst'];
				$this->ISBN = $COinS['rft_isbn'];
				$this->place = $COinS['rft_place'];
				$this->date = $COinS['rft_date'];
				$this->publisher = $COinS['rft_pub'];
			}
			else
			{
				$this->title = false;
			}
		}
		else
		{
			$this->errors[] = array('base-disabled','Deustche Nationalbibliothek (Berlin)');
			$this->title = false;
		}
	}

    /**
     * Title getter. Returns book title if found, FALSE if not found.
     * 
     * @return     mixed
     */
	public function getTitle()
	{
		return $this->title;
	}

	/**
	 * Book info getter
	 *
	 * @return     array
	 */
	public function getOutput()
	{
		// Array consisting of values, which keys are named
		// after English Wikipedia {{Cite book}} template,
		// with one exception: array '__names' consisting of
		// subarrays 'last' and 'first'.
		// These are equivalents of 'last' and 'first' fields,
		// and these are arrays; some Wikipedias
		// (ie. Polish) are using multiple author fields.
		// In that case each last name in array has first name
		// at the same index as it (ie. $this->lastNames[3]
		// has to correspond with $this->firstNames[3];
		// it has to be the same author)
		
		// Here you can also sort which fields are meant to be shown first
		// at the generated template. Simply the first one goes first. ;)
		return array(
						'__names' => Array(
										'last' => $this->lastNames,
										'first' => $this->firstNames
									),
						'title' => $this->title,
						'date' => $this->date,
						'publisher' => $this->publisher,
						'location' => $this->place,
						'isbn' => $this->ISBN,
						'__sourceurl' => $this->source // The only non-existant in {{Cite book}} field. Consists of source URL where the info was taken from
					);
	}

    /**
     * Returns $errors.
     * @see isbnDB::$errors
     */
    public function getErrors()
    {
        return $this->errors;
    }

}

?>