<?php
/**
 * @package Include/help/en
 */
?>
<h1>Recon Tasks </h1>

If you choose to edit or create a new task of network recon, then you should fill in the required fields in order the task could be processed properly. <br><br>

<b>Task name</b><br>

Name of the discovery task. It's only a descriptive value to could distinguish the task in case it would have several of them with different values of filter or template. <br><br>

<b>Discovery server</b><br>

Discovery Server assigned to the task. If you have several Discovery Servers, then you have to select here which of them you want to do the recon task. <br><br>

<b>Mode</b><br>

Task Mode to choose between "Network Scanning" and "Script Custom". The first mode is the conventional network recognition task, and the second is the manner in which the task is associated with a custom script<br><br>

<b>Network</b><br>

Network where you want to do the recognition. Use the network format/ bits mask. <b>CIDR</b>. For example 192.168.1.0/24 is a class C that includes the 192.168.1.0 to 192.168.1.255 adress. <br><br>

<b>Interval</b>
<br>
Repetition interval of systems search. Do not use intervals very shorts so Recon explores a network sending one Ping to each address. If you use recon networks very larges (for example a class A) combined with very short intervals (6 hours) you will be doing that <?php echo get_product_name(); ?> will be always bomb the network with pings, overloading it and also <?php echo get_product_name(); ?> unnecessarily.<br><br>

<b>Module template</b><br>

Plugins template to add to the discovered systems. When it detects a system that fits with the parameters for this task (OS, Ports), it will register it and will assign all the included modules in the defined plugin template. <br><br>

<b>OS</b><br>

Operative system to recognize. If you select one instead of any (Any) it will only be added the systems with this operative system.Consider that in some circumstances <?php echo get_product_name(); ?> can make a mistake when detecting systems, so this kind of "guess" is done with statistic patterns, that depending on some other factors could fail (networks with filters, security software, modified versions of the systems).To could use this method with security, you should have installed Xprobe2 in your system. <br><br>

<b>Ports</b><br>

Define some specific ports or an specific range, e.g: 22,23,21,80-90,443,8080.If you use this field,only the detected hosts that will have at least one of the ports here mentioned will be detected and added to the system. If one host is detected but it has not at least one of the ports opened, then it will be ignored. This, along with the filter by OS kind allows to detect the systems that are interesting for us,e.g: detecting that it is a router because it has the ports 23 and 57 opened and the system detect it as a "BSD" kind. <br><br>

<b>Group</b><br>

It is the group where we should add the discovered systems. It will must assign the new systems to one group. If it has already one specific group to locate the unclassified agents, then it could be a good idea to assign it there. <br><br>

<b>Incident</b><br>

Shows if by discovering new systems it create an incident or not. It will create one incident by task, not one for detected machine,summarizing all the detected new systems, and it will create it automatically in the group previously defined. <br><br>

<b>SNMP Default community</b><br>

Default SNMP community to use to discover the computers.<br><br>

<b>Comments</b><br>

Comments about discovery network task.<br><br>

<b>OS detection</b><br>

When choosing this option the scan will detect the OS.<br><br>

<b>Name resolution</b><br>

When choosing this option, the agent will be created with the name the computer, long as it is configured in the equipment, if not create the agent with the IP name.<br><br>

<b>Parent detection</b><br>

Selecting this option will detect in exploring whether computers connected to others and created as children.<br><br>

<b>Parent recursion</b><br>

Indicates the maximum number of recursion with which agents will be able to generate as parents and children, after scan.<br><br>


