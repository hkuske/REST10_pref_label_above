# REST10_pref_label_above

In version 10 a new user_preference value field_name_placement was introduced.
During an upgrade and during new installations this value is set for each user to "field_on_side" 
which leads to an old looking SFDC/Sugar 5 layout.

If you want to have a layout which is similar to the former 9.x layout you must set the user_preference 
for ield_name_placement to "field_on_top".

This script reads all user entries and sets the personal preference by switching over to the userid 
(sudo REST call) and then settig the preference by a me REST call.
 
The die() call at the beginning (POINT OF NO RETUN) must be uncommented to run the script. 
