<?php
/**
This library was created by Colin Wren
and uses the NHS Choices API

The aim behind this API is to even the playing field with the NHS Choices API. There appears to be a difference in available data types. Some endpoints offer JSON, some don't. Some data is in pages. This library reads the NHS Choices API for you, converts it all to JSON and goes through all pages. 

You will need to register with NHS Choices and use your key to use this library. You can get a key at: http://www.nhs.uk/aboutNHSChoices/professionals/syndication/Pages/Webservices.aspx

When using NHS Choices data please ensure you attribute any information to NHS Choices using the attribute function.
*/

if(!function_exists('json_decode')){
	throw new Exception('Choices needs the JSON PHP extension to work. Please enable this extension.');
}

class Choices{

	public $key;

	function __construct($key){
		//This is the key we'll be using to access information
		$this->key = $key;
		//This is the version of the Choices Library
		$this->version = "0.1";
	}

	/*
	This function takes a place name (verified), optional radius argument and a CSV list of different org types to check
	
	*/
	public function around($place,$orgs = "ambulance, gp, hospital, dentist, acute, pct, care, mh, independent, pharmacy, optician",$radius = 100){
		//Verify that the place name supplied actually exists
		
		//Create an array of the organisation types to look up
		$orgTypes = explode(", ", $orgs);
		
		//Nice big for loop now to grab the results based on the organisation types entered (uses radius if defined to limit returned results
		$results = array();
		for($i = 0; $i < count($orgTypes); $i++){
			//Now we search through the organisation types and see what we're looking for
			$type = "";
			if($orgTypes[$i] == "ambulance"){$type = "ambulancetrusts";}
			if($orgTypes[$i] == "gp"){$type = "gppractices";}
			if($orgTypes[$i] == "hospital"){$type = "hospitals";}
			if($orgTypes[$i] == "dentist"){$type = "dentists";}
			if($orgTypes[$i] == "acute"){$type = "acutetrusts";}
			if($orgTypes[$i] == "pct"){$type = "primarycaretrusts";}
			if($orgTypes[$i] == "care"){$type = "caretrusts";}
			if($orgTypes[$i] == "mh"){$type = "mentalhealthtrusts";}
			if($orgTypes[$i] == "independent"){$type = "independentsectororganisations";}
			if($orgTypes[$i] == "pharmacy"){$type = "pharmacies";}
			if($orgTypes[$i] == "optician"){$type = "opticians";}
			//Now we've got the organisation type set we need to load the org's search XML feed to get the results
			$doc = new DOMDocument();
			$xmlurl = "http://v1.syndication.nhschoices.nhs.uk/organisations/".$type."/place/".$place."/results.xml?apikey=".$this->key."&range=".$radius;
			$replaceurl = "http://v1.syndication.nhschoices.nhs.uk/organisations/".$type."/place/".$place."/results?apikey=".$this->key."&range=".$radius;
  			$doc->load( $xmlurl );
  			//Check if there's multiple pages of data by grabbing all link nodes and seeing if there's a rel attribute of last
  			$pages = 0;
  			$linknodes = $doc->getElementsByTagName("link");
  			foreach( $linknodes as $linknode){
  				//check the rel attribute
  				if($linknode->getAttribute("rel") == "last"){
  					//now we have the attribute then we want to read it's number
  					$pages = substr($linknode->getAttribute("href"),strlen($replaceurl."&page="));
  				}
  			}
  			//Load the information from the XML document (first page)
  			//grab all entries (search result)
  			$entrynodes = $doc->getElementsByTagName("entry");
  			foreach($entrynodes as $entrynode){
  				//for each entry grab the title of the search result
  				$titles = $entrynode->getElementsByTagName("title");
  				$title = $titles->item(0)->nodeValue;
  				
  				//for each entry grab the NHS Choices link
  				$entrylinknodes = $entrynode->getElementsByTagName("link");
  				$nhschoiceslink = "";
  				foreach( $entrylinknodes as $entrylinknode){
  					//check the rel attribute
  					if($entrylinknode->getAttribute("rel") == "alternate"){
  						//now we have the attribute then we want to read it's number
  						$nhschoiceslink = $entrylinknode->getAttribute("href");
  					}
  				}
  				
  				//for each entry grab the longitude and latitude and put into an array
  				$contentnode = $entrynode->getElementsByTagName("content")->item(0);
  				$coordsnode = $contentnode->getElementsByTagName("geographicCoordinates")->item(0);
  				$longnode = $coordsnode->getElementsByTagName("longitude");
  				$latnode = $coordsnode->getElementsByTagName("latitude");
  				$coords = array("long" => $longnode->item(0)->nodeValue, "lat" => $latnode->item(0)->nodeValue);
  				$result = array("title" => $title, "type" => $type, "link" => $nhschoiceslink, "coords" => $coords);
  				$results[] = $result;
  			}
  			//If there's more than one page
  			if($pages > 1){
  				//go through the pages and grab the data from them
  				for($j=2;$j<($pages + 1);$j++){
  					$doca = new DOMDocument();
					$xmlurla = "http://v1.syndication.nhschoices.nhs.uk/organisations/".$type."/place/".$place."/results.xml?apikey=".$this->key."&range=".$radius."&page=".$j;
  					$doca->load( $xmlurla );
  					//grab all entries (search result)
  					$entrynodesa = $doca->getElementsByTagName("entry");
  					foreach($entrynodesa as $entrynodea){
  						//for each entry grab the title of the search result
  						$titlesa = $entrynodea->getElementsByTagName("title");
  						$titlea = $titlesa->item(0)->nodeValue;
  				
  						//for each entry grab the NHS Choices link
  						$entrylinknodesa = $entrynodea->getElementsByTagName("link");
  						$nhschoiceslinka = "";
  						foreach( $entrylinknodesa as $entrylinknodea){
  							//check the rel attribute
  							if($entrylinknodea->getAttribute("rel") == "alternate"){
  								//now we have the attribute then we want to read it's number
  								$nhschoiceslinka = $entrylinknodea->getAttribute("href");
  							}
  						}
  				
  						//for each entry grab the longitude and latitude and put into an array
  						$contentnodea = $entrynodea->getElementsByTagName("content")->item(0);
  						$coordsnodea = $contentnodea->getElementsByTagName("geographicCoordinates")->item(0);
  						$longnodea = $coordsnodea->getElementsByTagName("longitude");
  						$latnodea = $coordsnodea->getElementsByTagName("latitude");
  						$coordsa = array("long" => $longnodea->item(0)->nodeValue, "lat" => $latnodea->item(0)->nodeValue);
  						$resulta = array("title" => $titlea, "type" => $type, "link" => $nhschoiceslinka, "coords" => $coordsa);
  						$results[] = $resulta;

  					}
  				}
  			}
  			
		}
		// return the results in an array
		return $results;		
	}
	
	/*
	This function prints out the NHS Choices syndication logo and if supplied with a URL will link to that
	
	USE: echo $choices->attribute("http://www.nhs.uk/whatever");
	
	*/
	public function attribute($url="http://www.nhs.uk/"){
		//return the HTML string to print the image and the url (if supplied)
		return "<a href=\"".$url."\" title=\"Visit NHS Choices\"><img src=\"http://www.nhs.uk/nhscwebservices/documents/logo1.jpg\" alt=\"Content supplied by NHS Choices\" /></a>";
	}
}


?>