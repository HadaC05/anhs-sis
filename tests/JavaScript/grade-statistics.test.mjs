import {test} from 'node:test';
import assert from 'node:assert/strict';
import {calculateStatistics, descriptorFor} from '../../resources/js/grade-statistics.js';
const bands = [{min:90,description:'Advancing'},{min:80,description:'Benchmarking'},{min:75,description:'Connecting'},{min:65,description:'Developing'},{min:0,description:'Emerging'}];
const periods = [{key:'t1',label:'Term 1'},{key:'t2',label:'Term 2'}];
test('rates exclude missing grades, retain failures and average available terms per learner', () => {
 const stats=calculateStatistics([{t1:80,t2:60},{t1:90,t2:100},{t1:null,t2:75},{t1:null,t2:null}],periods,bands);
 assert.deepEqual(stats.results.map(r=>[r.passed,r.failed,r.missing,r.rate]),[[2,0,2,100],[2,1,1,66.67],[2,1,1,66.67]]);
 assert.equal(stats.results[2].counts.Developing,1);assert.equal(stats.results[2].counts.Advancing,1);assert.equal(stats.results[2].counts.Connecting,1);assert.equal(stats.complete,2);
});
test('no data is distinct from a zero passing rate',()=>{
 assert.equal(calculateStatistics([],periods,bands).results[0].rate,null);
 assert.equal(calculateStatistics([{t1:0},{t1:64}],periods,bands).results[0].rate,0);
 assert.equal(calculateStatistics([{t1:''},{t1:'—'},{t1:101},{t1:-1}],periods,bands).results[0].graded,0);
});
test('boundaries and decimals follow the selected descriptor bands',()=>{
 for(const [grade,label] of [[0,'Emerging'],[64.99,'Emerging'],[65,'Developing'],[74.99,'Developing'],[75,'Connecting'],[79.99,'Connecting'],[80,'Benchmarking'],[89.99,'Benchmarking'],[90,'Advancing'],[100,'Advancing']])assert.equal(descriptorFor(grade,bands),label);
 assert.equal(descriptorFor('',bands),null);assert.equal(descriptorFor(101,bands),null);
 assert.equal(descriptorFor(85,[{min:90,description:'Outstanding'},{min:85,description:'Very Satisfactory'},{min:0,description:'Other'}]),'Very Satisfactory');
});
