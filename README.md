# Export Moodle Grades to external Webservice

A simple grade report that allows export grades to external webservice.

For this plugin to work you need the following:

* A remote webservice (currently SOAP)
* A function to get remote grades. You can configure:
 * URL and name of the function
 * parameters that identifies course and user who is asking for grades
* A function to check if user is allowed to send grades. You can configure:
 * URL and name of the function
 * parameters that identifies course and user who is asking if grades can be sent
* A function to send grades. You can configure:
 * URL and name of the function
 * parameter that identifies who is sending
 * parameter that will receive the course for which grades are being sent
 * parameter that will receive an array of users with grades,
 * and, if you want, parameters that will receive arrays with "insufficient attendances" and "insufficient mention"
