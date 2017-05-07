
<template>
    <div class="row">
        <div class="col-sm-9">
            <div v-if="uploadFlag">
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
            <div v-else>
                <div class="form-group">
                    <label>Enter JSON</label>
                    <textarea class="form-control" v-model="jsonString"  rows="4"></textarea>
                </div>
            </div>
        </div>
        <div class="col-sm-3">
            <button type="button" v-on:click="uploadFlag = true" class="btn btn-default">File Upload</button>

            <!-- Provides extra visual weight and identifies the primary action in a set of buttons -->
            <button type="button"  v-on:click="uploadFlag = false" class="btn btn-primary">Input Json</button>
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
        <div class="row">
            <div class="col-sm-9">
                <div class="Chart">
                    <h2>Scatter Chart For All Groups</h2>
                    <div id="containerFull" style="min-width: 315px; height: 400px"></div>
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
    import FileUpload from 'vue-upload-component';
    import Vue from 'vue';
    let Highcharts = require('highcharts');
    let _ = require('lodash');

    export default {
        components: {
            FileUpload,
        },

        props: ['token'],

        data: function () {
            return {
                uploadFlag : true,
                jsonString : '',
                json : {},
                showChart_1: false,
                showChart_2: false,
                chartError_1: '',
                chartError_2: '',


            }
        },

        mounted() {

        },
        methods: {

            formatJson(){
                let _this = this;
                let indexPromise = new Promise((resolve, reject)=>{
                    let index = _.findIndex(this.json.groups,(g)=>{
                        return g.groupId === 1;
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
                        this.createChart();
                        this.createFullChart();
                    });
            },

            createChart(){
                let p = new Promise((resolve, reject)=>{
                    let data = this.formatJson();
                    resolve(data);
                });
                p.then((success)=>{
                    if(success.length === 0 ){
                        return false;
                    }
                    Highcharts.chart('container', {
                        chart: {
                            type: 'scatter',
                            zoomType: 'xy'
                        },
                        title: {
                            text: 'For Group Id = 1'
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
                                    pointFormat: '{point.x} , {point.y} m'
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
                            text: 'For Group Id = 1'
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
                                    pointFormat: '{point.x} , {point.y} m'
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
