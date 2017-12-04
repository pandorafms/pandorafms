// ______                 __                     _______ _______ _______
//|   __ \.---.-.-----.--|  |.-----.----.---.-. |    ___|   |   |     __|
//|    __/|  _  |     |  _  ||  _  |   _|  _  | |    ___|       |__     |
//|___|   |___._|__|__|_____||_____|__| |___._| |___|   |__|_|__|_______|
//
// ============================================================================
// Copyright (c) 2007-2010 Artica Soluciones Tecnologicas, http://www.artica.es
// This code is NOT free software. This code is NOT licenced under GPL2 licence
// You cannnot redistribute it without written permission of copyright holder.
// ============================================================================

import java.util.*;
import java.net.MalformedURLException;
import java.net.URL;
import javax.xml.datatype.*;
import javax.xml.namespace.QName;
import Intel_Dcm.*; 

public class IntelDcmPlugin {
	private static QName qName = new QName("http://wsdl.intf.dcm.intel.com/", "Dcm");
	private static String result = "";
	private static URL url = null;
	private static boolean retry = true;
	private static int retries = 0;
	private static final int MAX_RETRIES = 10;	
	private static Dcm dcm;
		
	private static String getParam(String key, String[] params) {
		
		key = "--"+key;
		
		for (int i = 0; i < params.length; i++) {  
		
			if (key.equals(params[i])) {
				return params[i+1];
			} 
		}  
		
		return "";
	}
	
	private static QueryType strToQueryType(String str) {
		if (str.equals("max_pwr")) {
			return QueryType.MAX_PWR;
			
		} else if (str.equals("avg_pwr")) {
			return QueryType.AVG_PWR;
			
		} else if (str.equals("min_pwr")) {
			return QueryType.MIN_PWR;
			
		} else if (str.equals("max_avg_pwr")) {
			return QueryType.MAX_AVG_PWR;
			
		} else if (str.equals("total_max_pwr")) {
			return QueryType.TOTAL_MAX_PWR;
			
		} else if (str.equals("total_avg_pwr")) {
			return QueryType.TOTAL_AVG_PWR;
			
		} else if (str.equals("max_avg_pwr_cap")) {
			return QueryType.MAX_AVG_PWR_CAP;
			
		} else if (str.equals("total_max_pwr_cap")) {
			return QueryType.TOTAL_MAX_PWR_CAP;
			
		} else if (str.equals("total_avg_pwr_cap")) {
			return QueryType.TOTAL_AVG_PWR_CAP;
			
		} else if (str.equals("total_min_pwr")) {
			return QueryType.TOTAL_MIN_PWR;
			
		} else if (str.equals("min_avg_pwr")) {
			return QueryType.MIN_AVG_PWR;
			
		} else if (str.equals("max_inlet_temp")) {
			return QueryType.MAX_INLET_TEMP;
			
		} else if (str.equals("avg_inlet_temp")) {
			return QueryType.AVG_INLET_TEMP;
			
		} else if (str.equals("min_inlet_temp")) {
			return QueryType.MIN_INLET_TEMP;
			
		} else if (str.equals("ins_pwr")) {
			return QueryType.INS_PWR;
			
		}
		
		return null;
	}
	
	
	private static MetricType strToMetricType(String str) {
		if (str.equals("mnged_nodes_energy")) {
			return MetricType.MNGED_NODES_ENERGY;
			
		} else if (str.equals("mnged_nodes_energy_bill")) {
			return MetricType.MNGED_NODES_ENERGY_BILL;
			
		} else if (str.equals("it_eqpmnt_energy")) {
			return MetricType.IT_EQPMNT_ENERGY;
			
		} else if (str.equals("it_eqpmnt_energy_bill")) {
			return MetricType.IT_EQPMNT_ENERGY_BILL;

		} else if (str.equals("calc_cooling_energy")) {
			return MetricType.CALC_COOLING_ENERGY;
			
		} else if (str.equals("calc_cooling_energy_bill")) {
			return MetricType.CALC_COOLING_ENERGY_BILL;						
			 
		} else if (str.equals("mnged_nodes_pwr")) {
			return MetricType.MNGED_NODES_PWR;	

		} else if (str.equals("it_eqpmnt_pwr")) {
			return MetricType.IT_EQPMNT_PWR;	

		} else if (str.equals("calc_cooling_pwr")) {
			return MetricType.CALC_COOLING_PWR;	
  
		} else if (str.equals("avg_pwr_per_dimension")) {
			return MetricType.AVG_PWR_PER_DIMENSION;

		} else if (str.equals("derated_pwr")) {
			return MetricType.DERATED_PWR;
			
		} else if (str.equals("inlet_temperature_span")) {
			return MetricType.INLET_TEMPERATURE_SPAN;
			
		}			
	
		return null;
	}

	public static void printUsage () {
		
			System.out.print("\n\n Usage: DcmPlugin --server <server> --port <port> --action <action> [--value <value> and other values or option depending on action]\n\n");
	}
			
	public static XMLGregorianCalendar getEndDate() {

		try {
			GregorianCalendar gcal = new GregorianCalendar();

			return DatatypeFactory.newInstance().newXMLGregorianCalendar(gcal);
		
		} catch (DatatypeConfigurationException e) {
			return null;
		}

	}
	
	public static XMLGregorianCalendar getStartDate(int interval) {
		try {
			GregorianCalendar gcal = new GregorianCalendar();

			//Ge time in milliseconds
			long time = gcal.getTimeInMillis();
			
			//Convert time to seconds
			time = time / 1000;
			
			//Substract interval
			time = time - interval;
			
			//Convert to milliseconds
			time = time * 1000;
			
			//Set calendar to real time
			gcal.setTimeInMillis(time);
			
			XMLGregorianCalendar auxDate = DatatypeFactory.newInstance().newXMLGregorianCalendar(gcal);		
			
			return auxDate;
			
		} catch (DatatypeConfigurationException e) {
			return null;
		}
	}
	
	/**
	* @param args
	*/
	public static void main(String[] args) {
		
		while (retry) {
			
			retry = false;
			retries++;
			
			try{
				
				//Check arguments
				if (args.length < 6) {
					printUsage();
					System.exit(-1);
				}
				
				String server = getParam("server", args);
				String port = getParam("port", args);
				String action = getParam("action", args);
				String value = getParam("value", args);
				
				url = new URL("http://"+server+":"+port+"/DCMWsdl/dcm.wsdl");

				Dcm_Service dcmService = new Dcm_Service(url, qName);
				dcm = dcmService.getDcmPort();

				//Execute actions
				if (action.equals("resume_monitoring")) {
					dcm.setCollectionState(true);

				} else if (action.equals("suspend_monitoring")) {
					dcm.setCollectionState(false);

				} else if (action.equals("status_monitoring")) {
					if (dcm.getCollectionState()) {
						result = "1";
					} else {
						result = "0";
					}

				} else if (action.equals("set_power_sampling")) {
					dcm.setGlobalProperty(GlobalProperty.NODE_POWER_SAMPLING_FREQUENCY, value);

				} else if (action.equals("get_power_sampling")) {
					result = dcm.getGlobalProperty(GlobalProperty.NODE_POWER_SAMPLING_FREQUENCY);

				} else if (action.equals("set_power_granularity")) {				
					dcm.setGlobalProperty(GlobalProperty.NODE_POWER_MEASUREMENT_GRANULARITY, value);

				} else if (action.equals("get_power_granularity")) {
					result = dcm.getGlobalProperty(GlobalProperty.NODE_POWER_MEASUREMENT_GRANULARITY);

				} else if (action.equals("set_thermal_sampling")) {				
					dcm.setGlobalProperty(GlobalProperty.NODE_THERMAL_SAMPLING_FREQUENCY, value);

				} else if (action.equals("get_thermal_sampling")) {
					result = dcm.getGlobalProperty(GlobalProperty.NODE_THERMAL_SAMPLING_FREQUENCY);

				} else if (action.equals("set_thermal_granularity")) {				
					dcm.setGlobalProperty(GlobalProperty.NODE_THERMAL_MEASUREMENT_GRANULARITY, value);

				} else if (action.equals("get_thermal_granularity")) {
					result = dcm.getGlobalProperty(GlobalProperty.NODE_THERMAL_MEASUREMENT_GRANULARITY);

				} else if (action.equals("set_cooling_multiplier")) {				
					dcm.setGlobalProperty(GlobalProperty.COOLING_MULT, value);

				} else if (action.equals("get_cooling_multiplier")) {
					result = dcm.getGlobalProperty(GlobalProperty.COOLING_MULT);

				}  else if (action.equals("set_power_cost")) {				
					dcm.setGlobalProperty(GlobalProperty.COST_PER_KW_HR, value);

				} else if (action.equals("get_power_cost")) {
					result = dcm.getGlobalProperty(GlobalProperty.COST_PER_KW_HR);

				} else if(action.equals("add_entity")) {
					
					Property entityType = new Property();
					entityType.setName(EntityProperty.ENTITY_TYPE);
					entityType.setValue(getParam("type", args));
					
					Property address = new Property(); 
					address.setName(EntityProperty.BMC_ADDRESS);
					address.setValue(getParam("address", args));
					
					Property name = new Property();
					name.setName(EntityProperty.NAME);
					name.setValue(getParam("value", args));
					
					Property deratedPower = new Property();
					deratedPower.setName(EntityProperty.DERATED_PWR);
					deratedPower.setValue(getParam("derated_power", args));
					
					Property connectorName = new Property();
					connectorName.setName(EntityProperty.CONNECTOR_NAME);
					connectorName.setValue(getParam("connector", args));
					
					Property bmcUser = new Property();
					bmcUser.setName(EntityProperty.BMC_USER);
					bmcUser.setValue(getParam("bmc_user", args));

					Property bmcPass = new Property();
					bmcPass.setName(EntityProperty.BMC_PASSWORD);
					bmcPass.setValue(getParam("bmc_pass", args));					
					
					List<Property> properties = new ArrayList<Property>();
					
					properties.add(entityType);
					properties.add(address);
					properties.add(name);
					properties.add(deratedPower);
					properties.add(connectorName);
					properties.add(bmcUser);
					properties.add(bmcPass);
	
					int res = dcm.addEntity(EntityType.NODE, properties, true);
					System.out.print(res);
				} else if (action.equals("connector_list")) {

					List<ConnectorInfo> connectorList = dcm.getConnectorList();
					Iterator iter = connectorList.iterator();

					result = "";
					while (iter.hasNext()) {
						ConnectorInfo connector = (ConnectorInfo) iter.next();
						String name = connector.getDisplayName();
						String uname = connector.getUname();
						result = result+name+":"+uname+"|";
					}
					//Delete last "|"
					int lastIdx = result.length() - 1;
					result = result.substring(0, lastIdx);
				} else if (action.equals("entity_properties")) {
					String entId = getParam("entity_id",args);
					
					List<Property> properties = dcm.getEntityProperties(Integer.parseInt(entId));
					
					Iterator iter = properties.iterator();

					result = "";
					while (iter.hasNext()) {
						Property property = (Property) iter.next();

						EntityProperty name = property.getName();
						String val = property.getValue();
						result = result+name+":"+val+"|";
					}				
					//Delete last "|"
					int lastIdx = result.length() - 1;
					result = result.substring(0, lastIdx);
					
				} else if (action.equals("delete_entity")) {
					String entId = getParam("entity_id",args);
					
					dcm.removeEntity(Integer.parseInt(entId), true);
					
					System.out.println(1);
					
				} else if (action.equals("update_entity")) {
					
					Property address = new Property(); 
					address.setName(EntityProperty.BMC_ADDRESS);
					address.setValue(getParam("address", args));
					
					Property name = new Property();
					name.setName(EntityProperty.NAME);
					name.setValue(getParam("value", args));
					
					Property deratedPower = new Property();
					deratedPower.setName(EntityProperty.DERATED_PWR);
					deratedPower.setValue(getParam("derated_power", args));
									
					List<Property> properties = new ArrayList<Property>();
					
					properties.add(address);
					properties.add(name);
					properties.add(deratedPower);
					
					String entId = getParam("entity_id",args);				
					
					dcm.setEntityProperties(Integer.parseInt(entId), properties, false);
					
					System.out.print(1);
					
				} else if (action.equals("query_data")) {
					String entId = getParam("entity_id", args);

					QueryType queryType = strToQueryType(getParam("value", args));
					
					XMLGregorianCalendar startDate = getStartDate(300);
					XMLGregorianCalendar endDate = getEndDate();
										
					EnumerationRawData query = dcm.dumpMeasurementData(Integer.parseInt(entId), queryType, startDate, endDate);
									 
					List<RawPtData> queryData = query.getQueryData();

					Iterator iter = queryData.iterator();
					
					//Calculate an average
					int numberItems = 0;
					int accValue = 0;
					
					while (iter.hasNext()) {
						RawPtData data = (RawPtData) iter.next();
						
						accValue = accValue + data.getValue();
						numberItems++;
					}
					
					int avg = accValue / numberItems;
									
					result = String.valueOf(avg);
					
				} else if (action.equals("metric_data")) {
					String entId = getParam("entity_id", args);

					MetricType metricType = strToMetricType(getParam("value", args));
					
					XMLGregorianCalendar startDate = getStartDate(360);
					XMLGregorianCalendar endDate = getEndDate();
					
					List<AggregationPeriod> aggList = dcm.getMetricAggregationPeriodList(startDate, endDate, metricType);
					
					Iterator aggIter = aggList.iterator();
					
					int max = 0;
					
					while (aggIter.hasNext()) {
						
						AggregationPeriod aggData = (AggregationPeriod) aggIter.next();
												
						if (aggData.getValue() > max) {
							max = aggData.getValue();
							startDate = aggData.getStart();
							endDate = aggData.getEnd();
						}
					}

					EnumerationData metrics = dcm.getMetricData (Integer.parseInt(entId), metricType, AggregationLevel.SELF, startDate, endDate, -1);

					List<PtData> metricsData = metrics.getQueryData(); 

					Iterator iter = metricsData.iterator();
					
					//Calculate an average
					int numberItems = 0;
					int accValue = 0;
					
					while (iter.hasNext()) {
						
						PtData data = (PtData) iter.next();
						
						accValue = accValue + data.getValue();
						numberItems++;
					}
					
					int avg = accValue / numberItems;					
										
					result = String.valueOf(avg);
				} 
			} catch (Exception_Exception e) {
				System.out.println("Web Service Exception:");
				System.out.println(e);		
									
			} catch	(java.lang.Exception e2) {
				//Only retry if exception is different of MalformedURLException
				if (!(e2 instanceof MalformedURLException)) {
					
					//Only do MAX_RETRIES
					if (retries < MAX_RETRIES) {

						retry = true;
						
						try {
							//Sleep 1 seconds
							Thread.sleep(1000);
						} catch (InterruptedException ie) {
							//TODO InterruptedException handler
						}
					} else {
						System.out.println("Max number of retries reached: Timeout error");
					}
					
				}
				
			} finally{
				
	
				if (!result.equals("")) {
					System.out.print(result);
				}
			}
		}
	}
}
