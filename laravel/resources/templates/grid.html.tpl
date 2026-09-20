<%= name %>
<div class="days">
  <% for(var day=0; day<days.length; day++) {%>
     <div class="day">
       <div class="heading">
         <h3><%= days[day].name %></h3> — <i><%= days[day].date %></i>
       </div>
       <div class="shift-wrap">
         <div class="timegrid" data-start-hour="<%= gridStart %>" data-end-hour="<%= gridEnd %>">
           <div class="row hidden-xs hidden-sm">
             <div class="col-sm-2"></div>
             <div class="times col-sm-10">
               <div class="hours">
                 <% for(var hour = gridStart; hour < gridEnd; hour++) {%>
                   <div class="time"><%= hourLabel(hour) %></div>
                 <% } %>
               </div>
             </div> <!-- / .times -->
           </div> <!-- / .row -->

           <div class="row hidden-xs hidden-sm">
             <div class="col-sm-2"></div>
             <div class="background col-sm-10" style="height: 242px;">
               <div class="hours">
                 <% for(var hour = gridStart; hour < gridEnd; hour++) {%>
                   <div class="time"></div>
                 <% } %>
               </div>
             </div> <!-- / .background -->
           </div> <!-- / .row -->
         </div> <!-- / .timegrid -->
         <div class="department-wrap">
           <% for(var department=0; department < days[day].departments.length; department++) {%>
              <div class="department">
                <div class="title">
                  <a href="/department/<%= days[day].departments[department].id %>/edit"><%= days[day].departments[department].name %></a><br>
                </div>
                <ul class="shifts">
                  <% for(var shift=0; shift < days[day].departments[department].shifts.length; shift++) {%>
                     <li class="shift row" data-rows="6">
                       <div class="title col-sm-2" style="height: 12em;">
                         <a href="/schedule/<%= days[day].departments[department].shifts[shift].id %>/edit"><%= days[day].departments[department].shifts[shift].name %></a>
                       </div>

                       <div class="slots col-sm-10">
                         <% for(var slot=0; slot < days[day].departments[department].shifts[shift].slots.length; slot++) {%>

                            <span class="slot-wrap" data-start="<%= days[day].departments[department].shifts[shift].slots[slot].start_date %>" data-duration="<%= days[day].departments[department].shifts[shift].slots[slot].duration %>" data-row="<%= days[day].departments[department].shifts[shift].slots[slot].row %>">
                           <a class="slot empty" data-id="65" title="<%= days[day].departments[department].shifts[shift].slots[slot].title %>"><%= days[day].departments[department].shifts[shift].slots[slot].title %></a>
                         </span>
                         <% } %>
                       </div>
                     </li>
                     <% } %>
                </ul>
              </div>
              <% } %>
         </div> <!-- / .department-wrap -->
       </div> <!-- / .shift-wrap -->
     </div>
     <% } %>
</div>
