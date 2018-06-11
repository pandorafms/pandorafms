/*
Flot plugin for specifying range of thresolds on data. 
Controlled through the option "constraints" in a specific series

usage -
  $.plot($("#placeholder"), [{ data: [ ... ], constraints: [constraint1, constraint2]},{data:[...],constraints:[...]}])
  
  where constraint1 = {
					    threshold: 2,
					    color: "rgb(0,0,0)",
					    evaluate : function(y,threshold){ return y < threshold; }
					  }
	threshold -> y-limit on the plot.
	evaluate  -> the function which defines the limit.This function defines wheteher y < threshold or y > threshold.
	color     -> the color with which to plot portion of the graph which satisfies the limit condition.
	 


Internally, the plugin works by splitting the data into different series, one for each constraint.

*/
(function($){
  
    
  function init(plot){
   
    function plotWithMultipleThresholds(plot,s,datapoints){
			if(s.data && s.data.length > 0 && s.constraints && s.constraints.length>0){
			   var series = new Graph(s.data,s.constraints).getPlotData();
				for(var i=0;i<series.length;i++){
				var ss = $.extend({},s);
				ss.constraints = [];
				ss.data = series[i].data;
				ss.color = series[i].color;
				plot.getData().push(ss);
			}
		}
    }
	
	function Graph(dataset, constraints) {
	this._constraints = _getSortedConstraints(dataset,constraints);
	this._dataset = dataset;
	this._plotData = [];
		
	this.getPlotData = function() {
		
		if(this._constraints.length == 0)return [];
		
		for ( var i = this._constraints.length - 1; i >= 0 ; i--) {
			var constraint = this._constraints[i];
			if(null != constraint.threshold){
			var set = new Resolve(this._dataset).using(constraint.threshold, constraint.evaluate);
			this._plotData.push( {
				data : set,
				color : constraint.color
			});
			}
		}
		return this._plotData;
	};

    function Resolve(originalPonits) {
		this._originalPoints = originalPonits;
		this._data = [];
		this._getPointOnThreshold = _getPointOnThreshold;
		this.using = using ;
		
		function using(threshold, evaluate) {
			var count = 0;
			for ( var i = 0; i < this._originalPoints.length; i++) {
				var currentPoint = this._originalPoints[i];
				if (evaluate(currentPoint[1],threshold)) {
					if (i > 0
							&& (this._data.length == 0 || this._data[count - 1] == null)) {
						this._data[count++] = this._getPointOnThreshold(threshold,this._originalPoints[i - 1], currentPoint);
					}
					this._data[count++] = currentPoint;
				} else {
					if (this._data.length > 0 && this._data[count - 1] != null) {
						this._data[count++] = this._getPointOnThreshold(threshold,this._originalPoints[i - 1], currentPoint);
						this._data[count++] = null;
						this._data[count++] = null;
					}
				}
			}
			return this._data;
		}
		
			function _getPointOnThreshold(threshold, prevP, currP) {
			var currentX = currP[0];
			var currentY = currP[1];

			var prevX = prevP[0];
			var prevY = prevP[1];

			var slope = (threshold - currentY)
					/ (prevY - currentY);
			var xOnConstraintLine = slope * (prevX - currentX) + currentX;

			return [ xOnConstraintLine, threshold ];
		}	
	}

	function _getSortedConstraints(originalpoints,constraints){
		
		var dataRange = _findMaxAndMin(originalpoints);
		
		if(undefined == dataRange)return [];
		
		var max = dataRange.max;
		var min = dataRange.min;
		var thresholdRanges = [];
		var sortedConstraints = [];
		for(var i = 0; i < constraints.length ; i++){
			var constraint = constraints[i];
			var range = 0;
			if(constraint.evaluate(min,constraint.threshold)){
			   range = Math.abs(constraint.threshold - min);
			}else{
				range = Math.abs(max - constraint.threshold);
			}
			thresholdRanges.push({constraint:constraint,range:range});
		}
		QuickSort(thresholdRanges,function(obj1,obj2){ return obj1.range < obj2.range;});
		for(var i=thresholdRanges.length-1;i>=0;i--){
			sortedConstraints[i] = thresholdRanges[i].constraint;
		}
		return sortedConstraints;
	}

	function _findMaxAndMin(dataset){
		if(undefined == dataset)return undefined;
		var arr = [];
		for( var i=0;i<dataset.length;i++){
		   arr[i] = dataset[i][1];
		}
		QuickSort(arr,function(p1,p2){return p1 < p2;});
		return { min:arr[0],max:arr[arr.length-1]};
	}
	
}

    function QuickSort(dataset,comparator){
		sort(dataset, 0, dataset.length-1, comparator);
		
		function sort(array, left, right,comparator){
			if(right > left){
			   var pivotIndex = Math.floor(( left + right )/2);
			   var pivotNewIndex = partition(array,left,right,pivotIndex,comparator);
			   sort(array, left, pivotNewIndex - 1,comparator);
			   sort(array, pivotNewIndex + 1, right,comparator);
			  
			}
	   }
	   
	   function partition(array,left,right,pivotIndex,comparator){
			var pivot = array[pivotIndex];
			swap(array,pivotIndex,right);
			var storeIndex = left;
			for( var i= left ; i < right ; i++){
				if(comparator(array[i] , pivot)){
				   swap(array,i,storeIndex);
				   storeIndex = storeIndex + 1;
				}
			}
			swap(array,storeIndex,right);
		
		return storeIndex;
	   }
	   
	   function swap(array,index1,index2){
		var temp = array[index1];
		array[index1] = array[index2];
		array[index2] = temp;
	   }
    }


    plot.hooks.processRawData.push(plotWithMultipleThresholds);
 }
  
$.plot.plugins.push({
        init: init,
        name: 'multiple.threshold',
        version: '1.0'
    });
})(jQuery);


