<template>
  <div>
    <div class="modal fade" id="add-edit-sessions" tabindex="-1" aria-labelledby="exampleModalLabel" aria-hidden="true">
      <div class="modal-dialog modal-lg">
        <div class="modal-content">
          <div class="modal-header">
            <div class="modal-title modal-title-font" id="exampleModalLabel">{{ title }}</div>
          </div>
          <ValidationObserver v-slot="{ handleSubmit }">
            <form class="form-horizontal" id="form" @submit.prevent="handleSubmit(onSubmit)">
              <div class="modal-body">
                <div class="row">
                  <div class="col-6 col-md-6">
                    <ValidationProvider name="name" mode="eager" rules="required" v-slot="{ errors }">
                      <div class="form-group">
                        <label for="name">Session Name</label>
                        <input type="text" class="form-control" id="name" :class="{'error-border': errors[0]}" v-model="name" name="name" placeholder="Ex: 2023-2024" autocomplete="off">
                        <span class="error-message"> {{ errors[0] }}</span>
                      </div>
                    </ValidationProvider>
                  </div>
                  <div class="col-6 col-md-6">
                    <ValidationProvider name="from period" mode="eager" rules="required" v-slot="{ errors }">
                      <div class="form-group">
                        <label>From Period</label>
<!--                        <input type="text" class="form-control" :class="{'error-border': errors[0]}" id="fromPeriod" v-model="fromPeriod" name="fromPeriod" placeholder="Ex: 202301">-->
                        <datepicker v-model="fromPeriod" :format="customFormatter" input-class="form-control"></datepicker>
                        <span class="error-message"> {{ errors[0] }}</span>
                      </div>
                    </ValidationProvider>
                  </div>
                  <div class="col-6 col-md-6">
                    <ValidationProvider name="to year" mode="eager" rules="required" v-slot="{ errors }">
                      <div class="form-group">
                        <label>To Period</label>
<!--                        <input type="text" class="form-control" id="toPeriod" :class="{'error-border': errors[0]}" v-model="toPeriod" name="toPeriod" placeholder="Ex: 202401">-->
                        <datepicker v-model="toPeriod" :format="customFormatter" input-class="form-control"></datepicker>
                        <span class="error-message"> {{ errors[0] }}</span>
                      </div>
                    </ValidationProvider>
                  </div>
                  <div class="col-6 col-md-6">
                    <ValidationProvider name="name" mode="eager" rules="required" v-slot="{ errors }">
                      <div class="form-group">
                        <label for="batch_number">Batch Number</label>
                        <input type="text" class="form-control" id="batch_number" :class="{'error-border': errors[0]}" v-model="batch_number" name="name" placeholder="Enter Batch Number" autocomplete="off">
                        <span class="error-message"> {{ errors[0] }}</span>
                      </div>
                    </ValidationProvider>
                  </div>
                  <div class="col-6 col-md-6">
                    <ValidationProvider name="status" mode="eager" rules="required" v-slot="{ errors }">
                      <div class="form-group">
                        <label>Status</label>
                        <select name="status" v-model="status" class="form-control" id="status">
                          <option value="Y">Active</option>
                          <option value="N">Deactivate</option>
                        </select>
                        <span class="error-message"> {{ errors[0] }}</span>
                      </div>
                    </ValidationProvider>
                  </div>
                </div>
              </div>
              <div class="modal-footer">
                <submit-form :name="buttonText"/>
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
              </div>
            </form>
          </ValidationObserver>
        </div>
      </div>
    </div>
  </div>
</template>
<script>
import Datepicker from 'vuejs-datepicker';
import moment from "moment";
import {bus} from "../../app";
import {Common} from "../../mixins/common";


export default {
  mixins: [Common],
  components: {
    Datepicker
  },
  data() {
    return {
      title: '',
      sessionId: '',
      name: '',
      fromPeriod: '',
      toPeriod: '',
      batch_number: '',
      status: '',
      type: 'add',
      actionType: '',
      buttonShow: false,
      buttonText: ''
    }
  },
  computed: {},
  created() {
    // this.getData();
  },
  mounted() {
    $('#add-edit-sessions').on('hidden.bs.modal', () => {
      this.$emit('changeStatus')
    });
    bus.$on('add-edit-sessions', (row) => {
      if (row) {
        let instance = this;
        this.axiosGet('sessions/by-id/'+row.session_id,function(response) {
          var data = response.data;
          instance.title = 'Update Session';
          instance.buttonText = "Update";
          instance.sessionId = data.session_id;
          instance.name = data.name;
          instance.fromPeriod = data.from_period;
          instance.toPeriod = data.to_period;
          instance.batch_number = data.batch_number;
          instance.status = data.status;
          instance.buttonShow = true;
          instance.actionType = 'edit';
        },function(error){

        });
      } else {
        this.title = 'Add Session';
        this.buttonText = "Create";
        this.name = '';
        this.fromPeriod = '';
        this.toPeriod = '';
        this.batch_number = '';
        this.status = 'Y';
        this.actionType = 'add'
      }
      $("#add-edit-sessions").modal("toggle");
      // $(".error-message").html("");
    })
  },
  destroyed() {
    bus.$off('add-edit-sessions')
  },
  methods: {
    onSubmit() {
      let fromdate =  this.fromPeriod ? moment(this.fromPeriod).format('YYYY-MM-DD') : '';
      let todate =  this.toPeriod ? moment(this.toPeriod).format('YYYY-MM-DD') : '';

      this.$store.commit('submitButtonLoadingStatus', true);
      let url = '';
      if (this.actionType === 'add') url = 'sessions/create';
      else url = 'sessions/update/'+this.sessionId
      this.axiosPost(url, {
        name: this.name,
        fromPeriod: fromdate,
        toPeriod: todate,
        batch_number: this.batch_number,
        status: this.status
      }, (response) => {
        this.successNoti(response.message);
        $("#add-edit-sessions").modal("toggle");
        bus.$emit('refresh-datatable');
        this.$store.commit('submitButtonLoadingStatus', false);
      }, (error) => {
        this.errorNoti(error);
        this.$store.commit('submitButtonLoadingStatus', false);
      })
    },
    customFormatter(date) {
      return moment(date).format('YYYY-MM-DD');
    },
  }
}
</script>
