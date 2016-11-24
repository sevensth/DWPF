(function($){
    $.circuit = function(canvasId)
    {
        //const
        var PointTypePixel = 0;
        var PointTypeGrid = 1;

        //member vars
        var canvas;
        var context;
        var canvasRect;

        var circuitPathColorNormal = '#002E60';
        var circuitPathColorElectric = '#1774B9';

        var pathWidthBackbone = 3.0;
        var pathWidthMajorBranch = 2.0;
        var pathWidthMinorBranch = 1.0;

        var minV = 5;//per second
        var maxV = 20;//per second

        var gridWidth = 30;//20
        var gridXCount, gridYCount;
        var controlPointPercentage = 0.2*0.2;
        var controlPointCount;

        var controlPointList;
        var controlPointDebugList;
        var pathList;
        var controlPointLoop;
        var controlPointLoopCount;
        var controlPointValidLoopBegin = 0;

        var heartBox;
        var heartBoxMaxWidth = 320;
        var heartBoxMaxHeight = 240;

        var paused = true;

        //init
        if (!prepare(canvasId))
        {
            throw new Error('Canvas not found.');
        }

        //public funtions
        this.resume = _resume;
        this.pause = _pause;

        //private
        function _resume()
        {
            if (!paused)
            {
                return;
            }
            paused = false;

            context.beginPath(); // 开始路径绘制
            context.lineWidth = pathWidthBackbone; // 设置线宽
            context.strokeStyle = circuitPathColorNormal; // 设置线的颜色
            context.font         = 'normal  11px sans-serif';

            for(var key in controlPointList)
            {
                if (controlPointList[key].loop < controlPointValidLoopBegin)
                {
                    continue;
                }

                var controlPoint = gridPointToPxPoint(controlPointList[key]);
                context.moveTo(controlPoint.x, controlPoint.y);
                context.arc(controlPoint.x, controlPoint.y,pathWidthBackbone,0,2*Math.PI);
                context.stroke();

                context.fillText(controlPointList[key].x + ',' + controlPointList[key].y, controlPoint.x, controlPoint.y);
            }

            for(var pathIdx in pathList)
            {
                var path = pathList[pathIdx];
                if (!path) continue;

                var pathLength = path.length;
                for (var pointIdx = 0; pointIdx < pathLength; pointIdx++)
                {
                    var controlPoint = gridPointToPxPoint(path[pointIdx]);

                    if (pointIdx == 0)
                    {
                        context.moveTo(controlPoint.x, controlPoint.y);
                    }
                    else
                    {
                        context.lineTo(controlPoint.x, controlPoint.y);
                    }
                }
            }
            context.stroke();
        }
        function _pause()
        {
            paused = true;
        }

        function canvasResized()
        {
            _pause();

            canvas.width = $(canvas).innerWidth();
            canvas.height = $(canvas).innerHeight();
            canvasRect = {x:0, y:0, width:canvas.width, height:canvas.height};
            init();

            _resume();
        }

        function prepare(canvasId)
        {
            canvas = document.getElementById(canvasId);
            if (!canvas)
            {
                return false;
            }
            //context
            context = canvas.getContext('2d');

            //resize
            canvasResized();
            $(window).resize(canvasResized);

            //

            //done
            return true;
        }

        function init()
        {
            gridXCount = parseInt(canvasRect.width/gridWidth);
            gridYCount = parseInt(canvasRect.height/gridWidth);
            controlPointCount = parseInt(gridXCount * gridYCount * controlPointPercentage);
            buildHeartBox();
            _buildControlPointList();
            buildPathList();
        }

        function _buildControlPointList()
        {
            controlPointDebugList = [{"x":9,"y":12,"type":1,"loop":2,"path":5,"pathEnd":false,"pathBegin":false,"nextPath":4,"lastPath":7},{"x":14,"y":12,"type":1,"loop":2,"path":6,"pathEnd":false,"pathBegin":false,"nextPath":14,"lastPath":11},{"x":10,"y":5,"type":1,"loop":5,"path":15,"pathEnd":false,"pathBegin":false,"nextPath":16,"lastPath":16},{"x":13,"y":5,"type":1,"loop":5,"path":16,"pathEnd":false,"pathBegin":false,"lastPath":15,"nextPath":15},{"x":11,"y":0,"type":1,"loop":0,"path":0,"pathEnd":false,"pathBegin":true,"nextPath":1},{"x":18,"y":5,"type":1,"loop":5,"path":17,"pathEnd":true,"pathBegin":false,"lastPath":9},{"x":6,"y":1,"type":1,"loop":1,"path":1,"pathEnd":false,"pathBegin":false,"nextPath":7,"lastPath":0},{"x":24,"y":13,"type":1,"loop":1,"path":2,"pathEnd":false,"pathBegin":false,"nextPath":8,"lastPath":8},{"x":8,"y":2,"type":1,"loop":2,"path":7,"pathEnd":false,"pathBegin":false,"lastPath":1,"nextPath":5},{"x":28,"y":13,"type":1,"loop":1,"path":3,"pathEnd":false,"pathBegin":true,"nextPath":11},{"x":18,"y":3,"type":1,"loop":3,"path":9,"pathEnd":false,"pathBegin":true,"nextPath":17},{"x":16,"y":4,"type":1,"loop":4,"path":12,"pathEnd":true,"pathBegin":false,"lastPath":10},{"x":18,"y":3,"type":1,"loop":3,"path":10,"pathEnd":false,"pathBegin":true,"nextPath":12},{"x":30,"y":11,"type":1,"loop":3,"path":11,"pathEnd":false,"pathBegin":false,"lastPath":3,"nextPath":6},{"x":5,"y":10,"type":1,"loop":4,"path":13,"pathEnd":false,"pathBegin":false,"lastPath":4,"nextPath":18},{"x":5,"y":13,"type":1,"loop":1,"path":4,"pathEnd":false,"pathBegin":false,"nextPath":13,"lastPath":5},{"x":12,"y":10,"type":1,"loop":4,"path":14,"pathEnd":true,"pathBegin":false,"lastPath":6},{"x":26,"y":12,"type":1,"loop":2,"path":8,"pathEnd":false,"pathBegin":false,"lastPath":2,"nextPath":2},{"x":6,"y":8,"type":1,"loop":6,"path":18,"pathEnd":true,"pathBegin":false,"lastPath":13}];

            controlPointCount = 20;
            controlPointLoopCount = 7;
            var controlPointLoopCountX = parseInt(gridXCount/2);
            var controlPointLoopCountY = parseInt(gridYCount/2);
            controlPointLoop = new Array(controlPointLoopCount);
            controlPointList = [];
            for (var index = 0; index < controlPointCount; index++)
            {
                var controlPoint = controlPointDebugList[index];
                if (!controlPoint) break;

                controlPoint = {x:controlPoint.x, y:controlPoint.y, type:PointTypeGrid};

                if (pointInOnRect(controlPoint, heartBox))
                {
                    continue;
                }

                controlPointList[controlPoint.x + ',' + controlPoint.y] = controlPoint;
                //push into loop
                var loopX = controlPoint.x > controlPointLoopCountX ? gridXCount - 1 - controlPoint.x : controlPoint.x;
                var loopY = controlPoint.y > controlPointLoopCountY ? gridYCount - 1 - controlPoint.y : controlPoint.y;
                var loop = Math.min(loopX, loopY);
                if (loop < controlPointLoopCount)
                {
                    if (!controlPointLoop[loop])
                    {
                        controlPointLoop[loop] = new Array();
                    }
                    controlPoint.loop = loop;
                    controlPointLoop[loop].push(controlPoint);
                }
            }
        }

        function buildControlPointList()
        {
            controlPointDebugList = [];

            var controlPointLoopCountX = parseInt(gridXCount/2);
            var controlPointLoopCountY = parseInt(gridYCount/2);
            controlPointLoopCount = controlPointLoopCountX > controlPointLoopCountY ? controlPointLoopCountY : controlPointLoopCountX;
            controlPointLoop = new Array(controlPointLoopCount);
            controlPointList = [];
            for (var index = 0; index < controlPointCount; index++)
            {
                var controlPoint = {
                    x : Math.round(Math.random() * (gridXCount-1)),
                    y : Math.round(Math.random() * (gridYCount-1)),
                    type : PointTypeGrid
                };

                if (pointInOnRect(controlPoint, heartBox))
                {
                    continue;
                }
                controlPointDebugList.push(controlPoint);
                controlPointList[controlPoint.x + ',' + controlPoint.y] = controlPoint;
                //push into loop
                var loopX = controlPoint.x > controlPointLoopCountX ? gridXCount - 1 - controlPoint.x : controlPoint.x;
                var loopY = controlPoint.y > controlPointLoopCountY ? gridYCount - 1 - controlPoint.y : controlPoint.y;
                var loop = Math.min(loopX, loopY);
                if (loop < controlPointLoopCount)
                {
                    if (!controlPointLoop[loop])
                    {
                        controlPointLoop[loop] = new Array();
                    }
                    controlPoint.loop = loop;
                    controlPointLoop[loop].push(controlPoint);
                }
            }
        }

        function buildHeartBox()
        {
            var heartBoxPxWidth = canvasRect.width / 4;
            var heartBoxPxHeight = canvasRect.height / 4;
            heartBoxPxWidth = heartBoxPxWidth < heartBoxMaxWidth ? heartBoxPxWidth : heartBoxMaxWidth;
            heartBoxPxHeight = heartBoxPxHeight < heartBoxMaxHeight ? heartBoxPxHeight : heartBoxMaxHeight;
            heartBox = {
                x : Math.round((canvasRect.width - heartBoxPxWidth)/2/gridWidth),
                y : Math.round((canvasRect.height - heartBoxPxHeight)/2/gridWidth),
                width : Math.round(heartBoxPxWidth/gridWidth),
                height : Math.round(heartBoxPxHeight/gridWidth),
                type : PointTypeGrid
            };
        }

        function buildPathList()
        {
            pathList = new Array();

            var controlPointLoopCount = controlPointLoop.length;
            var searchDepth = Math.max(gridXCount, gridYCount);

            //search offsets
            for (var offset = 1; offset < searchDepth; offset++)
            {
                //loops
                for (var loop = 0; loop < controlPointLoopCount; loop++)
                {
                    if (loop < controlPointValidLoopBegin)
                    {
                        continue;
                    }

                    var loopRect = {x:loop, y:loop, width:gridXCount-loop-loop, height:gridYCount-loop-loop};

                    //leave heart box alone.
//                    if (loopRect.x >= heartBox.x || loopRect.y >= heartBox.y ||
//                        loopRect.x + loopRect.width <= heartBox.x + heartBox.width || loopRect.y + loopRect.height <= heartBox.y + heartBox.height
//                        )
//                    {
//                        break;
//                    }

                    var pointsInLoop = controlPointLoop[loop];
                    if (!pointsInLoop)
                    {
                        continue;
                    }

                    //points in loop
                    var pointsCountInLoop = pointsInLoop.length;
                    for (var loopIndex = 0; loopIndex < pointsCountInLoop; loopIndex++)
                    {
                        var targetPoint = pointsInLoop[loopIndex];
                        if (targetPoint.pathEnd == false)//路径当中的点都不再处理
                        {
                            continue;
                        }
                        //create new path if discrete
                        if (targetPoint.path == undefined)
                        {
                            targetPoint.path = pathList.length;
                            targetPoint.pathEnd = true;
                            targetPoint.pathBegin = true;
                            pathList.push([targetPoint]);
                        }

                        //伸展offset以寻找最近的连接点
                        var searchGridPoints = gridPointsInsideLoop(targetPoint, loopRect, offset);
                        var searchGridPointsCount = searchGridPoints.length;
                        for (var key = 0; key < searchGridPointsCount; key++)
                        {
                            var searchGirdPoint = searchGridPoints[key];
                            //从map中查找
                            var nearestControlPoint = controlPointList[searchGirdPoint.x + ',' + searchGirdPoint.y];
                            if (nearestControlPoint && (nearestControlPoint.path == undefined || nearestControlPoint.pathBegin))
                            {
                                targetPoint.pathEnd = false;
                                if (nearestControlPoint.pathBegin)
                                {
                                    //head of a path
                                    nearestControlPoint.lastPath = targetPoint.path;
                                    nearestControlPoint.pathBegin = false;
                                    targetPoint.nextPath = nearestControlPoint.path;
                                }
                                else
                                {
                                    //an discrete point
                                    nearestControlPoint.path = targetPoint.path;
                                    nearestControlPoint.pathEnd = true;
                                    pathList[targetPoint.path].push(nearestControlPoint);
                                }
                                break;
                            }
                        }
                    }
                }
            }

            //concatenate path
            var pathCount = pathList.length;
            //get base segment
            for (var pathIdx = 0; pathIdx < pathCount; pathIdx++)
            {
                var path = pathList[pathIdx];
                if (!path) continue;

                var firstPoint = path[0];
                if (firstPoint.pathBegin)
                {
                    while(1)
                    {
                        var endPoint = path[path.length-1];
                        var nextPath = pathList[endPoint.nextPath];
                        if (nextPath && nextPath.length > 0)
                        {
                            path = path.concat(nextPath);
                            pathList[pathIdx] = path;
                            pathList[endPoint.nextPath] = undefined;
                        }
                        else
                        {
                            break;
                        }
                    }
                }
            }
        }

        //utils
        function gridPointsInsideLoop(targetPoint, loopRect, offset)
        {
            if (offset <= 0)
            {
                return;
            }

            var points = [];
            var tmpPoint;
            for (var xStep = -offset; xStep <= offset; xStep++)
            {
                var yStep = offset - (xStep>0?xStep:-xStep);
                tmpPoint = {x:targetPoint.x+xStep, y:targetPoint.y+yStep, type:PointTypeGrid};
                if (pointInOnRect(tmpPoint, loopRect))
                {
                    points.push(tmpPoint);
                }
                if (yStep != 0)
                {
                    tmpPoint = {x:targetPoint.x+xStep, y:targetPoint.y-yStep, type:PointTypeGrid};
                    if (pointInOnRect(tmpPoint, loopRect))
                    {
                        points.push(tmpPoint);
                    }
                }
            }
            return points;
        }

        function gridPointToPxPoint(gridPoint)
        {
            return {
                x : (gridPoint.x+0.5) * gridWidth,
                y : (gridPoint.y+0.5) * gridWidth,
                type : PointTypePixel
            }
        }

        function pointInOnRect(point, rect)
        {
            if (point.x >= rect.x && point.y >= rect.y && point.x <= rect.width + rect.x && point.y <= rect.height + rect.y)
            {
                return true;
            }
            return false;
        }
    }

    //after document ready
    $(document).ready(function() {
        var homepageSliderCiruit = new $.circuit('circuit');
        homepageSliderCiruit.resume();
    });

})(jQuery)