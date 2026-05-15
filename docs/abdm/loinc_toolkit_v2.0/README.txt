********************************************************************************
		What is C-DAC's Toolkit for LOINC (CLNtk) ?
********************************************************************************

C-DAC's Toolkit for LOINC is a specially designed FOSS application for easy access and integration of LOINC standard in health care applications. Logical Observation Identifiers Names and Codes (LOINC®) is an international standard for laboratory tests, measurement, and observations.

The toolkit provides simple to use APIs for LOINC integration. It enables clinical informatician and researchers to find out relevant LOINC codes from its components including, long common name, short name, display name, and other related information.

Note: CLNtk is developed, build & tested on OpenJDK 17(13).
      Use this toolkit for development purpose.

********************************************************************************
		 CLNtk Prerequisite Software requirements
********************************************************************************
1. JDK 17 should be installed on machine
	Set the environment variables for Java.
	e.g.: Setting the environment variables for Java considering OpenJDK 17 is installed.  
		Windows: Add System Environment Variable: JAVA_HOME - C:\Program Files\OpenJDK\jdk-17.0.13
		LINUX: modify '/etc/profile': JAVA_HOME - export JAVA_HOME=/usr/lib/OpenJDK/jdk-17.0.13

	Append the path value for JAVA in the PATH environment variable.
	e.g.:  Windows: C:\Program Files\OpenJDK\jdk-17.0.13\bin
	       LINUX: modify '/etc/profile': export PATH=/usr/lib/OpenJDK/jdk-17.0.13/bin:$PATH
	
2. a) Go on https://loinc.org/
   b) Log in to your account. If account does not  exist, create an account by signing up.
   c) Go to Downloads tab.
   d) On this web page, download Loinc_x.zip. User must be signed in to download Loinc_x.zip.
   e) Unzip the downloaded Loinc_x.zip.
   f) Go to extracted folder and inside that go to "LoincTable" folder, From here you can obtain "Loinc.csv" file.
		Loinc_x > LoincTable > Loinc.csv
   g) Go to extracted folder and inside that go to "AccessoryFiles" folder,and inside that go to "PartFile" folder. From here you can obtain "Part.csv" file.		
		Loinc_x > AccessoryFiles > PartFile > Part.csv		
   h) Go to extracted folder and inside that go to "AccessoryFiles" folder,and inside that go to "PanelsAndForms" folder. From here you can obtain "PanelsAndForms.csv" file.  
		Loinc_x > AccessoryFiles > PanelsAndForms > PanelsAndForms.csv	
   g) Copy Loinc.csv, Part.csv and PanelsAndForms.csv to a folder. Use this folder path as LOINC CSV File Directory while filling details in config and show page.

4. Web server Apache-tomcat-10.1.9 should be installed on machine. 
	Set the environment variables for web server.
	e.g. : export TOMCAT_HOME=/usr/java/apache-tomcat-10.1.9 (modify '/etc/profile' - in case of LINUX)	    

5. Browser Compatibility  
   a)Edge: version 42 and higher
   b)Chrome: 88.0 and higher


********************************************************************************
	     CLNtk Folder Structure.
********************************************************************************

Extracted zip "loinc_toolkit" contains following:
1. loincserv.war - war ready to deploy in application server(Ex: tomcat).
2. README.txt - assist you how to set up Toolkit.
3. LICENSE.txt
4. NOTICE.txt
5. demo - Demonstration page to showcase the use of CLNtk APIs for using LOINC Panels as well as individual tests in a laboratory report.

********************************************************************************
		Configure and deploy CLNtk
********************************************************************************

1. Deploy loincserv.war to webapps folder of the application server directory.(Current version tested with Tomcat 10.1.9).

2. Configure application server(tomcat server) to use 1024M java maximum memory pool size.

3. Start the application server and once deployed, test it at the url:'http://localhost:8080/loincserv/config'.

4. In configuration detail page, provide the path for folder where LOINC CSV's(Loinc.csv, PanelsAndForms.csv, Part.csv) are kept, path for folder where loinc indexes will be generated and Log file Path. After providing the path and other details, submit the form.
   FolderForLoincCSV is the directory where required LOINC files are kept.
   FolderForIndexes is directory where required indexes will be generated.
   Ex: LOINC CSV File Directory : C:\Users\{username}\{FolderForLoincCSV}
   Ex: LOINC Index Directory : C:\Users\{username}\{FolderForIndexes}
   Ex: LOINC Log directory Path :D:\Logs\LoincToolkit

5. After successful submission or else try again with proper inputs. Please note that it might take few minutes to create indexes.

6. After index creation, access the API's using Swagger UI at http://localhost:{Port Number}/loincserv/swagger-ui.html  


********************************************************************************
		Configure and deploy Demo
********************************************************************************

The sample Laboratory Demo page shows the use of REST APIs provided by CLNtk. The demo page will help users to integrate CLNtk's APIs like search and lookup and how to record and identify various laboratory tests with LOINC standards code.

1. Add the url of Loinc server in system.properties file (e.g. {"loincservip":"http://localhost:8080/loincserv/"})

2. Paste the  demo folder in webapps folder of tomcat.

3. Access it in browser using http://{TOMCAT_IP_ADDRESS}:{TOMCAT_PORT}/demo 
  here, TOMCAT_IP_ADDRESS : Ip address of tomcat where demo is deployed.
		TOMCAT_PORT : Port of tomcat where demo is deployed.
		demo : Name of deployed  folder
  (e.g. http://localhost:8080/demo)
  
  
********************************************************************************
 In case of any queries / feedback please write to us at: sdk-enq@cdac.in
