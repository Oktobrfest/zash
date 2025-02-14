<div class="container mt-4">
	<div class="card">
		<div class="card-header bg-primary text-white">
			<h2 class="mb-0">Upload a OFX, QBO, or QFX file</h2>
		</div>
		<div class="card-body">
			<div class="alert alert-info" role="alert">
				<h5 class="alert-heading">Important Setup Instructions:</h5>
				<ul class="mb-0">
					<li>Make sure you have a bank account setup with the last four numbers of your bank account number as the last four digits of the account/ledger name.</li>
					<li>Make sure you have an 'UNALLOCATED' account setup in Equity with the reconciliation box checked.</li>
					<li class="text-muted"><i>TODO: validation for the above things and/or autocreation</i></li>
					<li>Webconnect for Quickbooks 2018 and up is a .qbo file which works better than qfx because it includes memo line. Most stable should be .ofx though.</li>
				</ul>
			</div>

			<?php echo $this->Form->create('Bulk', array('type' => 'file', 'class' => 'mt-4')); ?>
			<div class="form-group mb-4">
				<?php
				echo $this->Form->input('file', array(
					'type' => 'file',
					'label' => 'Select a Transactions File',
					'class' => 'form-control-file',
					'div' => false
				));
				?>
			</div>
			<div class="form-group">
				<?php
				echo $this->Form->button('Upload', array(
					'class' => 'btn btn-primary'
				));
				?>
			</div>
			<?php echo $this->Form->end(); ?>

			<?php if (!empty($transactions)): ?>
				<div class="mt-4">
					<h3 class="border-bottom pb-2">Transactions</h3>
					<div class="table-responsive">
						<table class="table table-hover">
							<thead class="thead-light">
							<tr>
								<th>Date</th>
								<th>Amount</th>
								<th>Description</th>
							</tr>
							</thead>
							<tbody>
							<?php foreach ($transactions as $transaction): ?>
								<tr>
									<td><?php echo h($transaction->date->format('Y-m-d')); ?></td>
									<td><?php echo h($transaction->amount); ?></td>
									<td><?php echo h($transaction->memo); ?></td>
								</tr>
							<?php endforeach; ?>
							</tbody>
						</table>
					</div>
				</div>
			<?php endif; ?>
		</div>
	</div>
</div>
