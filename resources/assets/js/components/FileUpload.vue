
<template>
    <div class="row">
        <div class="col-sm-9">
            <div>
                <div class="row">
                    <div class="col-sm-12">
                        <form enctype="multipart/form-data">
                            <div class="form-group">
                                <label>Upload File</label>
                                <input type="file" name="uploadFile" @change="uploadFileEvent" class="form-control" id="uploadFile"/>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
        <div class="row">
            <div class="col-sm-9">
                <div class="Chart">
                    <h2>Scatter Chart For Group Id : 1</h2>
                        <div id="container" v-if="showChart_1" style="min-width: 315px; height: 400px"></div>
                        <div v-else>{{chartError_1}}</div>
                </div>
            </div>
        </div>
        <div class="row" v-if="uploadFlag">
            <div class="col-sm-9">
                <div class="Chart">
                    <h2>Scatter Chart For All Groups</h2>
                    <div class="row">
                        <div class="col-sm-12">
                            <div class="dropdown">
                                <button class="btn btn-default dropdown-toggle" type="button" id="dropdownMenu1" data-toggle="dropdown" aria-haspopup="true" aria-expanded="true">
                                    {{selectGroupBtn}}
                                    <span class="caret"></span>
                                </button>
                                <ul class="dropdown-menu" aria-labelledby="dropdownMenu1">
                                    <li v-for="group in json.groups">
                                        <a href="#" v-on:click.prevent="buildChart(group.groupId)">{{group.groupId}}</a>
                                    </li>
                                    <li>
                                        <a v-on:click.prevent="buildChart('all')">All</a>
                                    </li>
                                </ul>
                            </div>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-sm-12">
                            <div id="containerFull" style="min-width: 315px; height: 400px"></div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>

<style>
    .home {
        position: relative;
    }
    .file-uploads {
        font-size: 18px;
        padding: .6em;
        font-weight: bold;
        border: 1px solid #888;
        background: #f3f3f3
    }
    .drop-active {
        top: 0;
        bottom: 0;
        right: 0;
        left: 0;
        position: absolute;
        opacity: .4;
        background: #000;
    }
    button {
        padding: .6em;
    }
    table {
        margin-bottom: 2em
    }
    table th,table td {
        padding: .4em;
        border: 1px solid #ddd
    }

</style>

<script>
    import Vue from 'vue';
    let Highcharts = require('highcharts');
    let _ = require('lodash');

    export default {

        props: ['token'],

        data: function () {
            return {
                uploadFlag : false,
                jsonString : '',
                json : {},
                showChart_1: false,
                showChart_2: false,
                chartError_1: '',
                chartError_2: '',
                selectGroupBtn:'All Groups',


            }
        },

        mounted() {

        },
        methods: {

            buildChart(id){
                if(id === 'all'){
                    this.selectGroupBtn = 'All Groups';
                    this.createFullChart();
                    return true;
                }
              this.selectGroupBtn = `Group Id: ${id}` ;
              this.createChart('containerFull',id);
            },

            updateJson(){
              this.json = JSON.parse(this.jsonString);

              this.createChart();
              this.createFullChart();
            },

            formatJson(groupId){

//                [
//                    {
//                        name: '',
//                        data:[[x,y]],
//                    }
//                ]
                let _this = this;
                let indexPromise = new Promise((resolve, reject)=>{
                    let index = _.findIndex(this.json.groups,(g)=>{
                        return g.groupId === groupId;
                    });
                    if(index > -1){
                        _this.showChart_1 = true;
                        _this.chartError_1 = '';
                        return resolve(index);
                    }
                    return reject('Cannot Found Group Id 1');
                });
                return indexPromise.then((groupIndex)=>{
                    let series = [];
                    _.forEach(this.json.groups[groupIndex]['peaks'],function(val, index){
                        let temp = {
                            name : val.sampleName,
                            data : []
                        };
                        _.forEach(val.eic.rt, function (r, key) {
                            if(r === _this.json.groups[groupIndex]['peaks'][index]['rt']){
                                temp.data.push(
                                    {
                                        x: r,
                                        y: _this.json.groups[0]['peaks'][index].eic.intensity[key],
                                        marker: {
                                            symbol: 'url(https://www.highcharts.com/samples/graphics/sun.png)'
                                        }

                                    })
                            }
                            temp.data.push([r,_this.json.groups[0]['peaks'][index].eic.intensity[key]]);
                        });
                        series.push(temp);
                    });
                    return series
                }).catch((e)=>{
                    _this.showChart_1 = false;
                    _this.chartError_1 = e;
                    return [];
                });

            },

            formatFullJson() {
                let _this= this;
                let series = [];
                
                _.forEach(this.json.groups, function (group, groupIndex) {
                    _.forEach(group['peaks'], function (peak, peakIndex) {
                        let findInSeries = _this.findInSeries(series, peak.sampleName);
                        if( findInSeries === -1){
                            let temp = {
                                name : peak.sampleName,
                                data : []
                            };
                            _.forEach(peak.eic.rt, function (r, key) {
                                temp.data.push([r,_this.json.groups[groupIndex]['peaks'][peakIndex].eic.intensity[key]]);
                            });
                            series.push(temp);
                        }else{
                            _.forEach(peak.eic.rt, function (r, key) {
                                series[findInSeries].data.push([r,_this.json.groups[groupIndex]['peaks'][peakIndex].eic.intensity[key]]);
                            });
                        }
                    });
                });
                return series;
                
            },

            findInSeries(series, key){
                let search = _.findIndex(series, function (s) {
                    return s.name === key;
                });
                return search;
            },

            uploadFileEvent(e){
                let files = e.target.files || e.dataTransfer.files;
                if (!files.length)
                    return;
                let formData = new FormData();
                formData.append('file',files[0]);
                formData.append('_token',this.token);
                this.$http.post('/file-upload', formData,
                    {
                        headers: {
                            'Content-Type': 'multipart/form-data',
                            'X-CSRF-Token' : this.token,
                        },
                        emulateJSON: true
                    }).then(response =>{
                        this.jsonString = response.bodyText;
                        this.json = response.body;
                        this.uploadFlag = true;
                        this.createChart('container', 1);
                        this.createFullChart();
                    });
            },

            createChart(containerId, groupId){
                let p = new Promise((resolve, reject)=>{
                    let data = this.formatJson(groupId);
                    resolve(data);
                });
                p.then((success)=>{
                    if(success.length === 0 ){
                        return false;
                    }
                    Highcharts.chart(containerId, {
                        chart: {
                            type: 'scatter',
                            zoomType: 'xy'
                        },
                        title: {
                            text: `For Group Id = ${groupId}`
                        },
                        subtitle: {
                            text: 'Elucidata'
                        },
                        xAxis: {
                            title: {
                                enabled: true,
                                text: 'Retention Time'
                            },
                            startOnTick: true,
                            endOnTick: true,
                            showLastLabel: true
                        },
                        yAxis: {
                            title: {
                                text: 'Intensity'
                            }
                        },
                        legend: {
                            layout: 'vertical',
                            align: 'left',
                            verticalAlign: 'top',
                            x: 100,
                            y: 70,
                            floating: true,
                            backgroundColor: (Highcharts.theme && Highcharts.theme.legendBackgroundColor) || '#FFFFFF',
                            borderWidth: 1
                        },
                        plotOptions: {
                            scatter: {
                                marker: {
                                    radius: 5,
                                    states: {
                                        hover: {
                                            enabled: true,
                                            lineColor: 'rgb(100,100,100)'
                                        }
                                    }
                                },
                                states: {
                                    hover: {
                                        marker: {
                                            enabled: false
                                        }
                                    }
                                },
                                tooltip: {
                                    headerFormat: '<b>{series.name}</b><br>',
                                    pointFormat: '{point.x} m, {point.y} m'
                                }
                            }
                        },
                        series:  success,
                    });
                });
            },

            createFullChart(){
                let promiseFull = new Promise((resolve, reject)=>{
                    let data = this.formatFullJson();
                    resolve(data);
                });
                promiseFull.then((success)=>{
                    if(success.length === 0 ){
                        return false;
                    }
                    Highcharts.chart('containerFull', {
                        chart: {
                            type: 'scatter',
                            zoomType: 'xy'
                        },
                        title: {
                            text: 'For All Groups'
                        },
                        subtitle: {
                            text: 'Elucidata'
                        },
                        xAxis: {
                            title: {
                                enabled: true,
                                text: 'Retention Time'
                            },
                            startOnTick: true,
                            endOnTick: true,
                            showLastLabel: true
                        },
                        yAxis: {
                            title: {
                                text: 'Intensity'
                            }
                        },
                        legend: {
                            layout: 'vertical',
                            align: 'left',
                            verticalAlign: 'top',
                            x: 100,
                            y: 70,
                            floating: true,
                            backgroundColor: (Highcharts.theme && Highcharts.theme.legendBackgroundColor) || '#FFFFFF',
                            borderWidth: 1
                        },
                        plotOptions: {
                            scatter: {
                                marker: {
                                    radius: 5,
                                    states: {
                                        hover: {
                                            enabled: true,
                                            lineColor: 'rgb(100,100,100)'
                                        }
                                    }
                                },
                                states: {
                                    hover: {
                                        marker: {
                                            enabled: false
                                        }
                                    }
                                },
                                tooltip: {
                                    headerFormat: '<b>{series.name}</b><br>',
                                    pointFormat: '{point.x} m, {point.y}'
                                }
                            }
                        },
                        series:  success,
                    });
                });
            },
        },
    }
</script>
