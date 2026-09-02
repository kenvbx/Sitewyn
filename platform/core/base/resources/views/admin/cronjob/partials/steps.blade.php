<div class="list-group list-group-flush">
  @foreach ($steps as $step)
    <div class="list-group-item px-0">
      <div class="row align-items-center">
        <div class="col-auto">
          <span class="badge bg-primary-lt text-primary">{{ $loop->iteration }}</span>
        </div>
        <div class="col fs-3">{{ $step }}</div>
      </div>
    </div>
  @endforeach
</div>
