*******************************************************************************
Collection statistics
*******************************************************************************

Prototype of an application for detailed statistics about the library collection. The main goal of this app is to show necessary data for evidence based decisions about the collection.

FEATURES

 - Storing titles, units and loans data from xlsx files with given structure
 - Counting usage for those and storing for later use
 - Display usage statistics for titles with different statuses based on criteria such as
    - date
    - status
    - granularity
    - min max filters
 
HOW IT WORKS

The app stores loan data on a day level. Based on the selected granularity the app aggregates this data for a certain level - day, month, year. The aggregation is made with a mean value. The mean is calculated by dividing the count of loaned units every day and the sum of active units on that day.

For better understanding of the result the app counts also a standard deviation for the mean. The deviation is then multiplied by two to cover cca 95 % of the values. The reason is to show how much the mean usage varies for each day during the watched period - especially month.

SECURITY
 - hide your config file in a separate folder and protect it

KNOWN PROBLEMS
 - Calculating the usage and processing files takes a long time - you need to extend you max execution time in php.ini
 - If during a catalogue life cycle a unit is removed and then added again after some time the app doesn't reflect that. The app doesn't store unit life cycle history. It stores only a date of creation and a date of deletion. It's up to the user which dates are chosen when exporting catalogue data.

TO DO:
- Add publication year to the visualisation
- How to reflect titles that had already some units discarded? Is it necessary?
- Running Count deletes already done exports
- Optimize speed (if possible)
- Simple user management
