
	function remove_URL(myVal)
	{

		if (myVal.length > 0)
		{
			myVal = myVal.replace(/<!-- m -->.*>(.*)<\/a><!-- m -->/g, "$1");
		}
		
	return myVal;
	}
	