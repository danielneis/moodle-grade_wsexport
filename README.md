# Export Moodle Grades to external Webservice

A simple grade report that allows export grades to external webservice.

For this plugin to work you need the following:

* A remote webservice (currently SOAP)
* A function to get remote grades
** You can configure: URL and name of the function, parameters that identifies course and user who is asking for grades
* A function to check if user is allowed to send grades
** You can configure: URL and name of the function, parameters that identifies course and user who is asking if grades can be sent
* A function to send grades
** You can configure: URL and name of the function, parameters that identifies who is sending, the course and users with grades,
and if you want, parameters to send insufficient attendances and "insufficient mention"
