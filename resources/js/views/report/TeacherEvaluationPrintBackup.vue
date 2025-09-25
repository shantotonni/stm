<template>
  <div class="content">
    <div class="container-fluid">
      <div class="row">
        <div class="col-xl-12">
          <div class="row">
            <div class="col-md-12">
              <div class="student" style="margin-top: 80px">
                <p style="text-align: center;font-weight: bold;border:1px solid;width: 400px; margin: 0 auto;border-radius: 15px;font-size: 22px">
                  Teacher Evaluation Report
                </p>
                <br>
                <div class="col-md-12 first_part" >
                  <div style="display:flex;">
                    <div class="col-md-7">
                      <p style="font-size: 18px;">Teacher Name <span style="margin-left: 70px">: {{teacher.name}}</span></p>
                    </div>
                    <div class="col-md-5">
                      <p style="font-size: 18px;">Email : {{teacher.email}}</p>
                    </div>
                  </div>
                  <div style="display:flex;">
                    <div class="col-md-7">
                      <p style="font-size: 18px;">Mobile <span style="margin-left: 168px">: {{teacher.mobile}}</span></p>
                    </div>
                    <div class="col-md-5">
                      <p style="font-size: 18px;">BMDC_NO : {{teacher.BMDC_NO}}</p>
                    </div>
                  </div>
                  <div style="display:flex;">
                    <div class="col-md-7">
                      <p style="font-size: 18px;" v-if="teacher.department">Department <span style="margin-left: 153px">: {{teacher.department.name}}</span></p>
                    </div>
                    <div class="col-md-5">
                      <p style="font-size: 18px;" v-if="teacher.designation">Designation : {{teacher.designation.name}}</p>
                    </div>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
    <data-export/>
  </div>
</template>

<script>
import {baseurl} from '../../base_url'
import Datepicker from 'vuejs-datepicker';
import moment from "moment";
import {bus} from "../../app";
export default {
  name: "List",
  components: {
    Datepicker
  },
  data() {
    return {
      teacher_evaluation: [],
      teacher: [],
    }
  },
  mounted() {
    document.title = 'Student Payment Report | Bill';
  },
  created() {
    axios.get(baseurl + `api/get-single-teachers-print/${this.$route.params.teacher_id}`).then((response)=>{
      this.teacher_evaluation = response.data.details
      this.teacher = response.data.teacher
      console.log(response)
      setTimeout(function(){
        window.print()
      },2000)
    });
  },
  methods: {
   //
  },
}
</script>

<style scoped>

</style>
